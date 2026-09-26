<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Renders an invoice to PDF for download and for email attachment.
 */
class InvoicePdf
{
    public static function data(Invoice $invoice): array
    {
        $invoice->loadMissing(['school', 'student', 'lines']);

        $student = $invoice->student;

        return [
            'invoice' => $invoice,
            'school' => $invoice->school,
            'student' => $student,
            'lines' => $invoice->lines,
            'schoolName' => $invoice->school?->name ?? config('app.name'),
            'studentName' => $student ? trim($student->first_name.' '.$student->last_name) : '—',
            'guardians' => $invoice->guardians(),
            'payUrl' => InvoiceLinks::payUrl($invoice),
            'generatedAt' => now(),
        ];
    }

    public static function output(Invoice $invoice): string
    {
        return Pdf::loadView('invoices.pdf', self::data($invoice))
            ->setPaper('a4')
            ->output();
    }

    public static function download(Invoice $invoice)
    {
        return Pdf::loadView('invoices.pdf', self::data($invoice))
            ->setPaper('a4')
            ->download(self::filename($invoice));
    }

    public static function filename(Invoice $invoice): string
    {
        return 'invoice-'.preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $invoice->invoice_number).'.pdf';
    }
}
