<?php

namespace Database\Factories\Domain\Finance\Models;

use App\Domain\Finance\Models\Invoice;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $taxRate = 15;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'invoice_number' => fake()->unique()->numerify('INV-######'),
            'issue_date' => fake()->date('Y-m-d', '-1 month'),
            'due_date' => fake()->date('Y-m-d', '+1 month'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'discount_amount' => 0,
            'total_amount' => $total,
            'amount_paid' => 0,
            'balance_due' => $total,
            'currency' => 'SAR',
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'vat_amount' => $taxAmount,
            'vat_rate' => $taxRate,
            'qr_code_data' => null,
        ];
    }
}
