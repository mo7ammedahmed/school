<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\InvoiceLinks;
use App\Domain\Finance\Services\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Number;

/**
 * One mailable covering the three things a guardian needs to hear about:
 * a new invoice, a reminder, and a receipt.
 */
class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KIND_ISSUED = 'issued';

    public const KIND_REMINDER = 'reminder';

    public const KIND_RECEIPT = 'receipt';

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $kind = self::KIND_ISSUED,
        public readonly ?Payment $payment = null,
    ) {}

    public function envelope(): Envelope
    {
        $school = $this->invoice->school;
        $from = $school?->email;

        return new Envelope(
            from: is_string($from) && filter_var($from, FILTER_VALIDATE_EMAIL)
                ? new Address($from, (string) $school->name)
                : null,
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        $invoice = $this->invoice;
        $invoice->loadMissing(['school', 'student', 'lines']);
        $student = $invoice->student;

        return new Content(
            view: 'mail.invoice',
            with: [
                'kind' => $this->kind,
                'invoice' => $invoice,
                'schoolName' => $invoice->school?->name ?? config('app.name'),
                'studentName' => $student ? trim($student->first_name.' '.$student->last_name) : 'your child',
                'amountDue' => $this->money((float) $invoice->balance_due),
                'total' => $this->money((float) $invoice->total_amount),
                'paidAmount' => $this->money((float) ($this->payment?->amount ?? $invoice->amount_paid)),
                'payUrl' => InvoiceLinks::payUrl($invoice),
                'dueDate' => $invoice->due_date?->toFormattedDateString(),
                'lines' => $invoice->lines,
                'heading' => $this->heading(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => InvoicePdf::output($this->invoice),
                InvoicePdf::filename($this->invoice),
            )->withMime('application/pdf'),
        ];
    }

    private function subjectLine(): string
    {
        $number = (string) $this->invoice->invoice_number;

        return match ($this->kind) {
            self::KIND_RECEIPT => "Payment received for invoice {$number}",
            self::KIND_REMINDER => "Reminder: invoice {$number} is still outstanding",
            default => "Invoice {$number} from ".($this->invoice->school?->name ?? config('app.name')),
        };
    }

    private function heading(): string
    {
        return match ($this->kind) {
            self::KIND_RECEIPT => 'Payment received — thank you',
            self::KIND_REMINDER => 'Friendly reminder about an outstanding invoice',
            default => 'A new invoice is ready for payment',
        };
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, (string) ($this->invoice->currency ?: 'SAR'), app()->getLocale());
    }
}
