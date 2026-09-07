<?php

declare(strict_types=1);

namespace App\Domain\Finance\DTOs;

final readonly class ProcessPaymentData
{
    public function __construct(
        public int $school_id,
        public int $student_id,
        public int $invoice_id,
        public string $payment_number,
        public string $payment_date,
        public float $amount,
        public string $currency,
        public string $payment_method,
        public string $status,
        public ?string $reference_number,
        public ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'payment_number' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
