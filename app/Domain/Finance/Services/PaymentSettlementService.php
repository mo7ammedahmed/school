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
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function handleWebhook(string $gateway, array $payload): void
    {
        $eventId = $payload['id'] ?? null;

        if (! $eventId) {
            Log::warning('Webhook received without event ID', ['payload' => $payload]);

            return;
        }

        $existingEvent = WebhookEvent::where('gateway', $gateway)
            ->where('event_id', $eventId)
            ->first();

        if ($existingEvent) {
            Log::info('Duplicate webhook event received', ['event_id' => $eventId]);

            return;
        }

        try {
            $payment = $this->resolvePayment($payload);

            // The school comes from the payment itself: `webhook_events.school_id`
            // is required, and gateway payloads do not reliably carry it.
            $schoolId = $payment?->school_id ?? $this->resolveSchoolId($payload);

            if (! $payment instanceof Payment) {
                $this->recordFailure($gateway, $eventId, $payload, $schoolId, 'Payment not found for webhook');

                return;
            }

            $webhookEvent = WebhookEvent::create([
                'school_id' => $schoolId,
                'gateway' => $gateway,
                'event_id' => $eventId,
                'event_type' => $payload['event']['type'] ?? ($payload['type'] ?? 'unknown'),
                'payload' => $payload,
                'status' => 'processing',
            ]);

            $transaction = GatewayTransaction::where('gateway_transaction_id', $eventId)->first();

            if (! $transaction) {
                $transaction = GatewayTransaction::create([
                    'school_id' => $payment->school_id,
                    'payment_id' => $payment->id,
                    'gateway' => $gateway,
                    'gateway_transaction_id' => $eventId,
                    'status' => 'completed',
                    'amount' => $this->amountFromPayload($payload, $payment),
                    'response' => $payload,
                ]);
            }

            $this->settlePayment($payment, $transaction);

            $webhookEvent->update(['status' => 'completed']);
        } catch (Exception $e) {
            WebhookEvent::where('gateway', $gateway)
                ->where('event_id', $eventId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            Log::error('Webhook processing failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Keep a record of a webhook we could not act on, as long as we can tell
     * which school it belongs to. Otherwise it would need a null school, which
     * the table does not allow.
     */
    private function recordFailure(
        string $gateway,
        string $eventId,
        array $payload,
        ?int $schoolId,
        string $message,
    ): void {
        Log::error('Webhook could not be matched to a payment', [
            'gateway' => $gateway,
            'event_id' => $eventId,
            'school_id' => $schoolId,
        ]);

        if ($schoolId === null) {
            return;
        }

        WebhookEvent::create([
            'school_id' => $schoolId,
            'gateway' => $gateway,
            'event_id' => $eventId,
            'event_type' => $payload['event']['type'] ?? ($payload['type'] ?? 'unknown'),
            'payload' => $payload,
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }

    /**
     * Gateways echo back our own metadata, so an id is the reliable key. The
     * payment number is kept as a fallback for older payloads.
     */
    private function resolvePayment(array $payload): ?Payment
    {
        $metadata = $payload['metadata'] ?? [];

        if (is_array($metadata) && ! empty($metadata['payment_id'])) {
            $payment = Payment::find((int) $metadata['payment_id']);

            if ($payment !== null) {
                return $payment;
            }
        }

        if (is_array($metadata) && ! empty($metadata['payment_number'])) {
            $payment = Payment::where('payment_number', $metadata['payment_number'])->first();

            if ($payment !== null) {
                return $payment;
            }
        }

        return null;
    }

    private function amountFromPayload(array $payload, Payment $payment): float
    {
        $amount = $payload['amount'] ?? null;

        if (! is_numeric($amount)) {
            return (float) $payment->amount;
        }

        // Moyasar and Stripe report minor units (halalas/cents).
        return round(((float) $amount) / 100, 4);
    }

    private function resolveSchoolId(array $payload): ?int
    {
        $metadata = $payload['metadata'] ?? [];

        if (is_array($metadata) && ! empty($metadata['payment_id'])) {
            $schoolId = Payment::whereKey((int) $metadata['payment_id'])->value('school_id');

            if ($schoolId !== null) {
                return (int) $schoolId;
            }
        }

        if (is_array($metadata) && ! empty($metadata['invoice_id'])) {
            $schoolId = Invoice::whereKey((int) $metadata['invoice_id'])->value('school_id');

            if ($schoolId !== null) {
                return (int) $schoolId;
            }
        }

        if (is_array($metadata) && ! empty($metadata['school_id']) && is_numeric($metadata['school_id'])) {
            return (int) $metadata['school_id'];
        }

        return null;
    }
}
