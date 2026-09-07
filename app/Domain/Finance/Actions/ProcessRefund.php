<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessRefund
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Payment $payment, float $amount, string $reason): Refund
    {
        if ($payment->school_id !== $this->school->id) {
            throw new Exception('Payment does not belong to this school.');
        }

        if ($payment->status !== 'paid') {
            throw new Exception('Can only refund paid payments.');
        }

        $totalRefunded = $payment->refunds()->sum('amount');
        $availableForRefund = $payment->amount - $totalRefunded;

        if ($amount <= 0) {
            throw new Exception('Refund amount must be greater than zero.');
        }

        if ($amount > $availableForRefund) {
            throw new Exception("Refund amount ({$amount}) exceeds available amount ({$availableForRefund}).");
        }

        if (empty($reason)) {
            throw new Exception('Refund reason is required.');
        }

        return DB::transaction(function () use ($payment, $amount, $reason) {
            $refund = Refund::create([
                'school_id' => $this->school->id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'amount' => $amount,
                'currency' => $payment->currency,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            $remaining = $payment->amount - $payment->refunds()->sum('amount') - $amount;
            if ($remaining <= 0) {
                $payment->update(['status' => 'refunded']);
            }

            $refund->invoice->update([
                'amount_paid' => max(0, $refund->invoice->amount_paid - $amount),
                'balance_due' => $refund->invoice->total_amount - max(0, $refund->invoice->amount_paid - $amount),
            ]);

            return $refund;
        });
    }
}
