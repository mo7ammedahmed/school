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
use App\Domain\Schools\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class PaymentSettlementService
{
    public function __construct(
        private readonly MoyasarGateway $gateway,
        private readonly TenantContext $tenants,
    ) {}

    /**
     * Mark a payment as settled and push the money onto its invoice.
     *
     * This is the single place that decides an invoice is paid, so the balance,
     * the status and the receipt all stay consistent.
     */
    public function settlePayment(Payment $payment, GatewayTransaction $transaction): void
    {
        $this->tenants->runFor((int) $payment->school_id, function () use ($payment, $transaction): void {
            $transaction->update([
                'status' => 'completed',
                'response' => [
                    'settled_at' => now()->toIso8601String(),
                    'amount' => $transaction->amount,
                ],
            ]);

            $this->markPaid($payment, $transaction->gateway_transaction_id, 'payment_settled');
        });
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
        // Settlement is always the payment's own school's work: the caller may
        // be a webhook with no session or a command with no request at all.
        $this->tenants->runFor((int) $payment->school_id, function () use ($payment, $reference, $action): void {
            $this->settleInTenant($payment, $reference, $action);
        });
    }

    private function settleInTenant(Payment $payment, ?string $reference, string $action): void
    {
        $alreadySettled = false;

        DB::transaction(function () use ($payment, $reference, $action, &$alreadySettled): void {
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return;
            }

            // Read under the lock: two deliveries for the same payment must not
            // both decide they are the one that settled it.
            $alreadySettled = $locked->status === 'paid';

            $locked->update([
                'status' => 'paid',
                'payment_date' => $locked->payment_date ?? now()->toDateString(),
                'reference_number' => $reference ?: $locked->reference_number,
            ]);

            $this->applyToInvoice($locked);

            AuditLog::create([
                'school_id' => $locked->school_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'entity_type' => 'Payment',
                'entity_id' => $locked->id,
                'new_values' => [
                    'status' => 'paid',
                    'reference_number' => $reference,
                ],
            ]);
        });

        // Only announce a first-time settlement so a duplicate confirmation
        // cannot trigger a second receipt email. Dispatched after the outermost
        // transaction commits, so a rollback cannot receipt money that was
        // never recorded.
        if (! $alreadySettled) {
            DB::afterCommit(fn () => PaymentSettled::dispatch($payment->id, 'paid'));
        }
    }

    /**
     * Move a settled payment onto its invoice: allocation row, amount paid,
     * outstanding balance and status.
     */
    private function applyToInvoice(Payment $payment): void
    {
        $invoice = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->first();

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

        // The delivery identifies its own school through the payment it names,
        // and from here on everything is that school's work: the secret that
        // verifies it, the event ledger, the transaction, the money. A webhook
        // has no session, so the context is pinned from the identified school
        // rather than inherited from whatever fallback the request had.
        return $this->tenants->runFor((int) $payment->school_id, function () use ($gateway, $eventId, $payload, $payment, $verifier): WebhookResult {
            $secret = GatewaySettings::for((int) $payment->school_id)->webhookSecret();

            if ($secret === null || $secret === '' || ! $verifier->verify($payload, $secret)) {
                Log::warning('Webhook signature verification failed', [
                    'gateway' => $gateway,
                    'event_id' => $eventId,
                    'school_id' => $payment->school_id,
                ]);

                return WebhookResult::rejected(401, 'The delivery could not be verified.');
            }

            try {
                // One transaction for the event record, the transaction record and
                // the money: a failure leaves nothing half-written for the provider
                // to build a retry on.
                return DB::transaction(fn (): WebhookResult => $this->settleVerifiedWebhook($gateway, $eventId, $payload, $payment));
            } catch (Throwable $e) {
                // A concurrent delivery may have won the insert and settled while
                // this attempt failed on the unique index. That settlement stands;
                // this attempt is a replay, not a failure to record.
                $winner = WebhookEvent::query()
                    ->where('gateway', $gateway)
                    ->where('event_id', $eventId)
                    ->first();

                if ($winner !== null && $winner->status === 'completed') {
                    return WebhookResult::replayed();
                }

                $this->recordFailure($gateway, $eventId, (int) $payment->school_id, $e->getMessage());

                Log::error('Webhook processing failed', [
                    'gateway' => $gateway,
                    'event_id' => $eventId,
                    'school_id' => $payment->school_id,
                    'error' => $e->getMessage(),
                ]);

                return WebhookResult::retryable('The delivery could not be processed.');
            }
        });
    }

    /**
     * Act on a delivery whose signature has already been verified.
     *
     * @param  array<string, mixed>  $payload
     */
    private function settleVerifiedWebhook(string $gateway, string $eventId, array $payload, Payment $payment): WebhookResult
    {
        $event = WebhookEvent::query()->firstOrCreate(
            ['gateway' => $gateway, 'event_id' => $eventId],
            [
                'school_id' => $payment->school_id,
                'event_type' => $this->eventType($payload),
                'payload' => $payload,
                'status' => 'processing',
            ],
        );

        // A completed delivery is a replay: answer 2xx so the provider stops
        // retrying, and settle nothing. A failed one is a second attempt, and
        // the unique (gateway, event_id) index is what makes that distinction
        // safe when two deliveries arrive at once.
        if (! $event->wasRecentlyCreated && $event->status === 'completed') {
            return WebhookResult::replayed();
        }

        $event->update(['status' => 'processing', 'error_message' => null]);

        $data = $this->paymentData($payload);
        $transactionId = $this->stringOrNull($data['id'] ?? null);

        if ($transactionId === null) {
            return $this->refuse($event, 422, 'The delivery carried no gateway transaction id.');
        }

        $status = $this->askGateway($gateway, $payment, $transactionId);

        if ($status === null) {
            $event->update(['status' => 'failed', 'error_message' => 'The gateway could not be reached.']);

            return WebhookResult::retryable('The gateway could not be reached.');
        }

        // Lock the payment before anything is written for it, so two deliveries
        // for the same payment are serialised rather than raced.
        $lockedPayment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->first();

        if ($lockedPayment === null) {
            return $this->refuse($event, 422, 'The payment no longer exists.');
        }

        $transaction = $this->gatewayTransaction($gateway, $lockedPayment, $transactionId, $payload);
        $gatewayStatus = (string) ($status['status'] ?? 'unknown');

        if ($gatewayStatus !== 'paid') {
            // Whatever the payload claims, the gateway's own answer is the
            // record — and it is not money.
            $transaction->update([
                'status' => $gatewayStatus,
                'amount' => $this->amountFromMinorUnits($status['amount'] ?? null) ?? $transaction->amount,
                'currency' => strtoupper((string) ($status['currency'] ?? $lockedPayment->currency)),
                'response' => $status,
            ]);

            $event->update([
                'status' => 'failed',
                'error_message' => 'The gateway reports the payment as '.$gatewayStatus.'.',
            ]);

            return WebhookResult::ignored('The gateway reports the payment as '.$gatewayStatus.'.');
        }

        $mismatch = $this->mismatch($lockedPayment, $status);

        if ($mismatch !== null) {
            $event->update(['status' => 'failed', 'error_message' => $mismatch]);

            return WebhookResult::rejected(422, $mismatch);
        }

        $transaction->update([
            'payment_id' => $lockedPayment->id,
            'status' => 'completed',
            'amount' => $this->amountFromMinorUnits($status['amount'] ?? null) ?? $lockedPayment->amount,
            'currency' => strtoupper((string) ($status['currency'] ?? $lockedPayment->currency)),
            'response' => $status,
        ]);

        $this->markPaid($lockedPayment, $transactionId, 'payment_settled');

        $event->update(['status' => 'completed', 'error_message' => null]);

        return WebhookResult::settled();
    }

    /**
     * Ask the gateway what it actually did.
     *
     * The inbound status and amount are claims made by whoever sent the
     * request; this is the answer the settlement is allowed to rest on. A null
     * return means the question could not be asked, which the caller treats as
     * "retry later" rather than "not paid".
     *
     * @return array<string, mixed>|null
     */
    private function askGateway(string $gateway, Payment $payment, string $transactionId): ?array
    {
        if ($gateway !== 'moyasar') {
            // Only Moyasar implements PaymentGatewayInterface, and only its
            // verifier is registered. A gateway added to the registry without a
            // client must never be settled on the payload's word.
            throw new LogicException(sprintf('No gateway client is registered for [%s].', $gateway));
        }

        $this->gateway->useSchoolSettings((int) $payment->school_id);

        $result = $this->gateway->verifyPayment($transactionId);

        if (($result['success'] ?? false) !== true) {
            return null;
        }

        $data = $result['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    private function gatewayTransaction(string $gateway, Payment $payment, string $transactionId, array $payload): GatewayTransaction
    {
        $transaction = GatewayTransaction::query()
            ->where('gateway', $gateway)
            ->where('gateway_transaction_id', $transactionId)
            ->lockForUpdate()
            ->first();

        if ($transaction !== null) {
            return $transaction;
        }

        return GatewayTransaction::create([
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

    /**
     * Whether the gateway's answer agrees with the local record.
     *
     * A gateway that reports a different amount or currency is not answering
     * about this payment, whatever its metadata says.
     *
     * @param  array<string, mixed>  $status
     */
    private function mismatch(Payment $payment, array $status): ?string
    {
        $expectedAmount = (int) round(((float) $payment->amount) * 100);
        $reportedAmount = $status['amount'] ?? null;

        if (! is_numeric($reportedAmount) || (int) $reportedAmount !== $expectedAmount) {
            return sprintf(
                'The gateway reports %s minor units but the payment is %d.',
                is_numeric($reportedAmount) ? (string) ((int) $reportedAmount) : 'an unreadable amount',
                $expectedAmount,
            );
        }

        $expectedCurrency = strtoupper((string) $payment->currency);
        $reportedCurrency = strtoupper((string) ($status['currency'] ?? ''));

        if ($reportedCurrency !== $expectedCurrency) {
            return sprintf(
                'The gateway reports %s but the payment is in %s.',
                $reportedCurrency === '' ? 'no currency' : $reportedCurrency,
                $expectedCurrency,
            );
        }

        return null;
    }

    /**
     * Keep a record of a delivery we could not act on. Best effort and outside
     * the failed transaction, so the provider's retry meets a row that says
     * what happened rather than silence.
     */
    private function recordFailure(string $gateway, string $eventId, int $schoolId, string $message): void
    {
        try {
            $event = $this->tenants->runFor($schoolId, fn () => WebhookEvent::query()->firstOrNew(['gateway' => $gateway, 'event_id' => $eventId]));

            // Never demote a completed delivery: its settlement already stands,
            // and a failed mark would invite a retry that should not happen.
            if ($event->exists && $event->status === 'completed') {
                return;
            }

            $event->fill([
                'status' => 'failed',
                'error_message' => $message,
            ]);

            if (! $event->exists) {
                $event->fill([
                    'school_id' => $schoolId,
                    'event_type' => 'unknown',
                    'payload' => [],
                ]);
            }

            $event->save();
        } catch (Throwable) {
            // If even the record cannot be written, the log line above it is the
            // only trace left, and it is enough to investigate from.
        }
    }

    private function refuse(WebhookEvent $event, int $httpStatus, string $message): WebhookResult
    {
        $event->update(['status' => 'failed', 'error_message' => $message]);

        return WebhookResult::rejected($httpStatus, $message);
    }

    private function amountFromMinorUnits(mixed $amount): ?float
    {
        return is_numeric($amount) ? round(((float) $amount) / 100, 4) : null;
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

        // Deliberately unscoped, and this is the only crossing in the flow: the
        // metadata is a claim that says which school's secret must verify the
        // delivery, so the payment has to be found before the school is known.
        // Nothing is settled on the strength of this lookup — the verified
        // settlement below runs inside the identified school.
        if (! empty($metadata['payment_id'])) {
            $payment = Payment::withoutSchoolScope()->find((int) $metadata['payment_id']);

            if ($payment !== null) {
                return $payment;
            }
        }

        if (! empty($metadata['payment_number']) && is_string($metadata['payment_number'])) {
            return Payment::withoutSchoolScope()->where('payment_number', $metadata['payment_number'])->first();
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
