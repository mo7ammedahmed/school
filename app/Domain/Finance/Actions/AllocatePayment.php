<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentAllocation;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class AllocatePayment
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Payment $payment, array $allocations): array
    {
        if ($payment->school_id !== $this->school->id) {
            throw new Exception('Payment does not belong to this school.');
        }

        if ($allocations === []) {
            throw new Exception('At least one allocation is required.');
        }

        $totalAllocated = array_sum(array_column($allocations, 'amount'));
        $existingAllocated = $payment->allocations()->sum('amount');

        if ($totalAllocated + $existingAllocated > $payment->amount) {
            throw new Exception('Total allocation exceeds payment amount.');
        }

        $created = [];

        return DB::transaction(function () use ($payment, $allocations, &$created): array {
            foreach ($allocations as $allocation) {
                if (empty($allocation['invoice_id']) || !isset($allocation['amount']) || $allocation['amount'] <= 0) {
                    throw new Exception('Each allocation must have an invoice_id and a positive amount.');
                }

                $invoice = Invoice::where('school_id', $this->school->id)
                    ->where('id', $allocation['invoice_id'])
                    ->first();

                if (!$invoice) {
                    throw new Exception("Invoice not found for allocation.");
                }

                $paymentAllocation = PaymentAllocation::create([
                    'school_id' => $this->school->id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => (float) $allocation['amount'],
                ]);

                $newAmountPaid = $invoice->amount_paid + (float) $allocation['amount'];
                $newBalance = max(0, $invoice->balance_due - (float) $allocation['amount']);
                $invoice->update([
                    'amount_paid' => $newAmountPaid,
                    'balance_due' => $newBalance,
                    'status' => $newBalance <= 0 ? 'paid' : 'partially_paid',
                ]);

                $created[] = $paymentAllocation;
            }

            return $created;
        });
    }
}
