<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Finance\Events\PaymentSettled;
use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentAllocation;
use App\Domain\Finance\Models\WebhookEvent;
use App\Domain\Finance\Webhooks\WebhookResult;
use App\Domain\Finance\Webhooks\WebhookVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentSettlementService
{
    /**
     * Mark a payment as settled and push the money onto its invoice.
     *
     * This is the single place that decides an invoice is paid, so the balance,
     * the status and the receipt all stay consistent.
     */
    public function settlePayment(Payment $payment, GatewayTransaction $transaction): void
    {
        $transaction->update([
            'status' => 'completed',
            'response' => [
                'settled_at' => now()->toIso8601String(),
                'amount' => $transaction->amount,
            ],
        ]);

        $this->markPaid($payment, $transaction->gateway_transaction_id, 'payment_settled');
    }

    /**
     * Settle a payment that arrived outside a gateway — a confirmed bank
     * transfer, for example. Recorded distinctly so the audit trail shows a
     * human confirmed it rather than the provider.
     */
    public function settleManually(Payment $payment, ?string $reference = null, string $action = 'payment_confirmed'): void
    {
        $this->markPaid($payment, $reference, $action);
    }

    private function markPaid(Payment $payment, ?string $reference, string $action): void
    {
        $alreadySettled = $payment->status === 'paid';

        DB::transaction(function () use ($payment, $reference, $action): void {
            $payment->update([
                'status' => 'paid',
                'payment_date' => $payment->payment_date ?? now()->toDateString(),
                'reference_number' => $reference ?: $payment->reference_number,
            ]);

            $this->applyToInvoice($payment);

            AuditLog::create([
                'school_id' => $payment->school_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'entity_type' => 'Payment',
                'entity_id' => $payment->id,
                'new_values' => [
                    'status' => 'paid',
                    'reference_number' => $reference,
                ],
            ]);
        });

        // Only announce a first-time settlement so a duplicate confirmation
        // cannot trigger a second receipt email.
        if (! $alreadySettled) {
            PaymentSettled::dispatch($payment->id, 'paid');
        }
    }

    /**
     * Move a settled payment onto its invoice: allocation row, amount paid,
     * outstanding balance and status.
     */
    private function applyToInvoice(Payment $payment): void
    {
        $invoice = Invoice::query()->find($payment->invoice_id);

        if ($invoice === null) {
            Log::warning('Settled payment has no invoice to apply to', ['payment_id' => $payment->id]);

            return;
        }

        $amount = (float) $payment->amount;

        PaymentAllocation::firstOrCreate(
            [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
            ],
            [
                'school_id' => $payment->school_id,
                'amount' => $amount,
            ],
        );

        // Recompute from the sum of allocations so a partially-paid invoice that
        // receives a second payment lands on the right balance.
        $allocated = (float) $invoice->payments()
            ->where('status', 'paid')
            ->where('id', '!=', $payment->id)
            ->sum('amount') + $amount;

        $total = (float) $invoice->total_amount;
        $balance = max(0.0, round($total - $allocated, 4));

        $invoice->forceFill([
            'amount_paid' => min($allocated, $total),
            'balance_due' => $balance,
            'status' => $balance <= 0.0 ? 'paid' : 'partially_paid',
            'paid_at' => $balance <= 0.0 ? now() : null,
        ])->save();
    }

    /**
     * Handle one gateway delivery.
     *
     * The order is the security property: resolve which school the delivery
     * claims to belong to, verify the delivery with that school's own secret,
     * and only then touch the database. A delivery that cannot be verified is
     * refused without a write.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(string $gateway, array $payload, WebhookVerifier $verifier): WebhookResult
    {
        $eventId = $this->stringOrNull($payload['id'] ?? null);

        if ($eventId === null) {
            Log::warning('Webhook received without an event id', ['gateway' => $gateway]);

            return WebhookResult::ignored('The delivery carried no event id.');
        }

        $payment = $this->resolvePayment($this->paymentData($payload));

        if (! $payment instanceof Payment) {
            Log::warning('Webhook could not be matched to a payment', [
                'gateway' => $gateway,
                'event_id' => $eventId,
            ]);

            return WebhookResult::ignored('The delivery could not be matched to a payment.');
        }

        $secret = GatewaySettings::for((int) $payment->school_id)->webhookSecret();

        if ($secret === null || $secret === '' || ! $verifier->verify($payload, $secret)) {
            Log::warning('Webhook signature verification failed', [
                'gateway' => $gateway,
                'event_id' => $eventId,
                'school_id' => $payment->school_id,
            ]);

            return WebhookResult::rejected(401, 'The delivery could not be verified.');
        }

        return $this->processVerifiedWebhook($gateway, $eventId, $payload, $payment);
    }

    /**
     * Act on a delivery whose signature has already been verified.
     *
     * @param  array<string, mixed>  $payload
     */
    private function processVerifiedWebhook(string $gateway, string $eventId, array $payload, Payment $payment): WebhookResult
    {
        $existing = WebhookEvent::query()
            ->where('gateway', $gateway)
            ->where('event_id', $eventId)
            ->first();

        if ($existing !== null && in_array($existing->status, ['completed', 'processing'], true)) {
            return WebhookResult::replayed();
        }

        $data = $this->paymentData($payload);
        $transactionId = $this->stringOrNull($data['id'] ?? null);

        $webhookEvent = WebhookEvent::updateOrCreate(
            ['gateway' => $gateway, 'event_id' => $eventId],
            [
                'school_id' => $payment->school_id,
                'event_type' => $this->eventType($payload),
                'payload' => $payload,
                'status' => 'processing',
                'error_message' => null,
            ],
        );

        if ($transactionId === null) {
            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => 'The delivery carried no gateway transaction id.',
            ]);

            return WebhookResult::rejected(422, 'The delivery carried no gateway transaction id.');
        }

        $transaction = GatewayTransaction::query()
            ->where('gateway', $gateway)
            ->where('gateway_transaction_id', $transactionId)
            ->first();

        if ($transaction === null) {
            $transaction = GatewayTransaction::create([
                'school_id' => $payment->school_id,
                'payment_id' => $payment->id,
                'gateway' => $gateway,
                'gateway_transaction_id' => $transactionId,
                'status' => 'pending',
                'currency' => (string) $payment->currency,
                'amount' => $payment->amount,
                'payload' => $payload,
            ]);
        }

        try {
            $this->settlePayment($payment, $transaction);
        } catch (Throwable $e) {
            $webhookEvent->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            Log::error('Webhook settlement failed', [
                'gateway' => $gateway,
                'event_id' => $eventId,
                'school_id' => $payment->school_id,
                'error' => $e->getMessage(),
            ]);

            return WebhookResult::retryable('The delivery could not be settled.');
        }

        $webhookEvent->update(['status' => 'completed', 'error_message' => null]);

        return WebhookResult::settled();
    }

    /**
     * The payment object inside a delivery.
     *
     * Moyasar wraps the payment in a `data` object, the way Stripe and most
     * providers do; the flat shape older deliveries used is not a shape the
     * provider documents.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function paymentData(array $payload): array
    {
        $data = $payload['data'] ?? null;

        return is_array($data) ? $data : [];
    }

    /**
     * Which payment the provider is writing about, from the metadata it echoes
     * back. This only identifies the school whose secret verifies the delivery;
     * nothing is settled on the strength of it.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolvePayment(array $data): ?Payment
    {
        $metadata = $data['metadata'] ?? null;

        if (! is_array($metadata)) {
            return null;
        }

        if (! empty($metadata['payment_id'])) {
            $payment = Payment::query()->find((int) $metadata['payment_id']);

            if ($payment !== null) {
                return $payment;
            }
        }

        if (! empty($metadata['payment_number']) && is_string($metadata['payment_number'])) {
            return Payment::query()->where('payment_number', $metadata['payment_number'])->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function eventType(array $payload): string
    {
        $type = $payload['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : 'unknown';
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return null;
    }
}
