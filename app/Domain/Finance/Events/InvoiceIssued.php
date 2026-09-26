<?php

declare(strict_types=1);

namespace App\Domain\Finance\Events;

use App\Domain\Finance\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment an invoice becomes payable. Listeners use this to deliver the
 * invoice and its payment link to the guardian automatically.
 */
class InvoiceIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(public Invoice $invoice) {}
}
