<?php

namespace Database\Factories;

use App\Domain\Finance\Models\Invoice;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxRate = 0.15;
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'invoice_number' => 'INV-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'issue_date' => fake()->date('Y-m-d'),
            'due_date' => fake()->date('Y-m-d', '+30 days'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'discount_amount' => 0,
            'total_amount' => $total,
            'amount_paid' => 0,
            'balance_due' => $total,
            'currency' => 'SAR',
            'status' => 'draft',
            'notes' => null,
            'vat_amount' => null,
            'vat_rate' => null,
            'qr_code_data' => null,
        ];
    }
}
