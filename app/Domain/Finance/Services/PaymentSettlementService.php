<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use Exception;
use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\WebhookEvent;
use App\Domain\Compliance\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentSettlementService
{
    public function settlePayment(Payment $payment, GatewayTransaction $transaction): void
    {
        DB::transaction(function () use ($payment, $transaction) {
            $payment->update([
                'status' => 'paid',
                'reference_number' => $transaction->gateway_transaction_id,
            ]);

            $transaction->update([
                'status' => 'completed',
                'response' => [
                    'settled_at' => now()->toIso8601String(),
                    'amount' => $transaction->amount,
                ],
            ]);

            AuditLog::create([
                'school_id' => $payment->school_id,
                'user_id' => auth()->id(),
                'action' => 'payment_settled',
                'entity_type' => 'Payment',
                'entity_id' => $payment->id,
                'new_values' => [
                    'status' => 'paid',
                    'gateway_transaction_id' => $transaction->gateway_transaction_id,
                ],
            ]);
        });
    }

    public function handleWebhook(string $gateway, array $payload): void
    {
        $eventId = $payload['id'] ?? null;

        if (!$eventId) {
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

        DB::transaction(function () use ($gateway, $payload, $eventId) {
            $webhookEvent = WebhookEvent::create([
                'school_id' => $this->resolveSchoolId($payload),
                'gateway' => $gateway,
                'event_id' => $eventId,
                'event_type' => $payload['event']['type'] ?? 'unknown',
                'payload' => $payload,
                'status' => 'processing',
            ]);

            try {
                $payment = Payment::where('payment_number', $payload['metadata']['payment_id'] ?? '')->first();

                if (!$payment) {
                    throw new Exception('Payment not found for webhook');
                }

                $transaction = GatewayTransaction::where('gateway_transaction_id', $eventId)->first();

                if (!$transaction) {
                    $transaction = GatewayTransaction::create([
                        'school_id' => $payment->school_id,
                        'payment_id' => $payment->id,
                        'gateway' => $gateway,
                        'gateway_transaction_id' => $eventId,
                        'status' => 'completed',
                        'amount' => $payload['amount'] / 100,
                        'response' => $payload,
                    ]);
                }

                $this->settlePayment($payment, $transaction);

                $webhookEvent->update(['status' => 'completed']);
            } catch (Exception $e) {
                $webhookEvent->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('Webhook processing failed', [
                    'event_id' => $eventId,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    private function resolveSchoolId(array $payload): ?int
    {
        return $payload['metadata']['school_id'] ?? null;
    }
}
