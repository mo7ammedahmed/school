<?php

namespace Database\Factories\Domain\Finance\Models;

use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $invoice = Invoice::factory()->create();
        $amount = fake()->randomFloat(2, 10, $invoice->balance_due);

        return [
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'payment_number' => fake()->unique()->numerify('PAY-######'),
            'payment_date' => fake()->date('Y-m-d'),
            'amount' => $amount,
            'currency' => 'SAR',
            'payment_method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'online']),
            'status' => 'paid',
            'reference_number' => fake()->optional()->numerify('REF-######'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
