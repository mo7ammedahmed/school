<?php

declare(strict_types=1);

namespace App\Domain\Finance\DTOs;

final readonly class CreateInvoiceData
{
    public function __construct(
        public int $school_id,
        public int $student_id,
        public string $invoice_number,
        public string $issue_date,
        public string $due_date,
        public float $subtotal,
        public float $tax_amount,
        public ?float $tax_rate,
        public float $discount_amount,
        public float $total_amount,
        public float $amount_paid,
        public float $balance_due,
        public string $currency,
        public string $status,
        public ?string $notes,
        public ?float $vat_amount,
        public ?float $vat_rate,
        public ?string $qr_code_data,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'invoice_number' => ['required', 'string', 'max:255', 'unique:invoices,invoice_number'],
            'issue_date' => ['required', 'date', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'balance_due' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'qr_code_data' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
