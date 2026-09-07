<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\Refund;
use App\Models\Payment;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function offlinePayments(): Response
    {
        $payments = Payment::where('payment_method', 'bank_transfer')->latest()->paginate(15);
        return inertia('finance/payments/offline', ['payments' => $payments]);
    }

    public function returnPayment(Payment $payment): Response
    {
        return inertia('finance/payments/return', ['payment' => $payment]);
    }

    public function refunds(): Response
    {
        $refunds = Refund::with('invoice.student')->latest()->paginate(15);
        return inertia('finance/refunds/index', ['refunds' => $refunds]);
    }
}
