<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentSettlementService;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class SettlePayment
{
    public function __construct(
        private readonly School $school,
        private readonly PaymentSettlementService $paymentSettlementService,
    ) {}

    public function execute(Payment $payment): Payment
    {
        if ($payment->school_id !== $this->school->id) {
            throw new Exception('Payment does not belong to this school.');
        }

        if ($payment->status === 'paid') {
            throw new Exception('Payment has already been settled.');
        }

        if ($payment->status === 'refunded') {
            throw new Exception('Cannot settle a refunded payment.');
        }

        if ($payment->amount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'paid']);

            $this->paymentSettlementService->settlePayment($payment, $payment->gatewayTransactions()->first());

            $totalAllocated = $payment->allocations()->sum('amount');
            $remaining = $payment->amount - $totalAllocated;

            if ($remaining > 0) {
                $unpaidInvoice = Invoice::where('school_id', $this->school->id)
                    ->where('student_id', $payment->student_id)
                    ->where('status', '!=', 'paid')
                    ->orderBy('due_date')
                    ->first();

                if ($unpaidInvoice) {
                    $unpaidInvoice->update([
                        'amount_paid' => $unpaidInvoice->amount_paid + $remaining,
                        'balance_due' => max(0, $unpaidInvoice->balance_due - $remaining),
                        'status' => $unpaidInvoice->balance_due - $remaining <= 0 ? 'paid' : 'partially_paid',
                    ]);
                }
            }

            return $payment->fresh();
        });
    }
}
