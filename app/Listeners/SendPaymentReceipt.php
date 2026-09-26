<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Finance\Events\PaymentSettled;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\InvoiceDeliveryService;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Confirms a settled payment to the guardian, with an updated invoice attached.
 */
class SendPaymentReceipt
{
    public function __construct(private readonly InvoiceDeliveryService $delivery) {}

    public function handle(PaymentSettled $event): void
    {
        $payment = Payment::with('invoice')->find($event->paymentId);

        if ($payment === null || $payment->invoice === null) {
            return;
        }

        // Only settled money is worth confirming; failed/refunded states are
        // handled by their own flows.
        if ($event->status !== 'paid') {
            return;
        }

        try {
            $result = $this->delivery->deliver($payment->invoice, InvoiceMail::KIND_RECEIPT, $payment);

            Log::info('Payment receipt sent to guardian', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'channels' => $result['channels'],
                'errors' => $result['errors'],
            ]);
        } catch (Throwable $e) {
            Log::error('Payment receipt delivery failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
