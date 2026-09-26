<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Finance\Events\InvoiceIssued;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\Finance\Services\InvoiceDeliveryService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers an issued invoice (PDF + payment link) to the student's guardian.
 *
 * Runs synchronously so the guardian is contacted as part of the same request
 * that issued the invoice; failures are logged rather than thrown because a
 * mail outage must not roll back the invoice itself.
 */
class SendInvoiceToGuardian
{
    public function __construct(private readonly InvoiceDeliveryService $delivery) {}

    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice;

        if (! GatewaySettings::for((int) $invoice->school_id)->autoSend()) {
            Log::info('Automatic invoice delivery is disabled for this school', ['invoice_id' => $invoice->id]);

            return;
        }

        try {
            $result = $this->delivery->deliver($invoice);

            Log::info('Invoice delivered to guardian', [
                'invoice_id' => $invoice->id,
                'channels' => $result['channels'],
                'recipients' => $result['recipients'],
                'errors' => $result['errors'],
            ]);
        } catch (Throwable $e) {
            Log::error('Invoice delivery failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
