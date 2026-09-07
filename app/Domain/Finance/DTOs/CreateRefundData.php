<?php

declare(strict_types=1);

namespace App\Domain\Finance\DTOs;

final readonly class CreateRefundData
{
    public function __construct(
        public int $school_id,
        public int $payment_id,
        public int $invoice_id,
        public float $amount,
        public string $currency,
        public string $reason,
        public string $status,
        public ?int $processed_by,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'reason' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'string', 'max:50'],
            'processed_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
