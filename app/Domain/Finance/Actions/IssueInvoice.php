<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Events\InvoiceIssued;
use App\Domain\Finance\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Moves an invoice from draft to payable and announces it.
 *
 * Delivery itself is a listener concern so issuing an invoice never fails just
 * because an SMS provider is down.
 */
class IssueInvoice
{
    public function execute(Invoice $invoice): Invoice
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->forceFill([
                'status' => 'issued',
                'issue_date' => $invoice->issue_date ?? now()->toDateString(),
            ])->save();
        });

        InvoiceIssued::dispatch($invoice->refresh());

        return $invoice;
    }
}
