<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentAllocation;
use App\Domain\People\Models\Student;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('school_id');
        $query = Payment::where('school_id', $schoolId)
            ->with(['student', 'invoice'])
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('payment_number', 'like', "%{$search}%");
        }

        $payments = $query->paginate(25);

        return Inertia::render('finance/payments/index', [
            'payments' => $payments,
        ]);
    }

    public function create()
    {
        $students = Student::where('school_id', session('school_id'))->get();
        $invoices = Invoice::where('school_id', session('school_id'))
            ->where('status', '!=', 'paid')
            ->get();

        return Inertia::render('finance/payments/create', [
            'students' => $students,
            'invoices' => $invoices,
        ]);
    }

    public function store(StorePaymentRequest $request)
    {
        $schoolId = session('school_id');
        $data = $request->validated();
        $data['school_id'] = $schoolId;

        // Honour an explicitly recorded number (offline/cash entry);
        // otherwise generate a unique system number.
        if (empty($data['payment_number'])) {
            $data['payment_number'] = $this->generatePaymentNumber($schoolId);
        }

        $data['currency'] ??= 'SAR';

        // A gateway payment is settled by webhook later; an offline payment
        // (cash, bank transfer, cheque) is recorded in its submitted state.
        if (empty($data['status'])) {
            $data['status'] = in_array($data['payment_method'] ?? '', ['moyasar', 'hyperpay', 'stripe'])
                ? 'pending'
                : 'paid';
        }

        DB::transaction(function () use ($schoolId, $data) {
            $payment = Payment::create($data);

            $invoice = Invoice::findOrFail($data['invoice_id']);
            $remainingBalance = $invoice->balance_due - $data['amount'];

            if ($remainingBalance < 0) {
                throw new Exception('Payment amount exceeds invoice balance.');
            }

            $settlesInvoice = in_array($payment->status, ['paid', 'captured', 'settled'], true);

            PaymentAllocation::create([
                'school_id' => $schoolId,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
            ]);

            if ($settlesInvoice) {
                $invoice->update([
                    'amount_paid' => $invoice->amount_paid + $data['amount'],
                    'balance_due' => $remainingBalance,
                    'status' => $remainingBalance <= 0 ? 'paid' : 'partially_paid',
                ]);
            }

            if (in_array($data['payment_method'], ['moyasar', 'hyperpay', 'stripe'])) {
                GatewayTransaction::create([
                    'school_id' => $schoolId,
                    'payment_id' => $payment->id,
                    'gateway' => $data['payment_method'],
                    'status' => 'pending',
                    'currency' => $data['currency'] ?? 'SAR',
                    'amount' => $data['amount'],
                ]);
            }
        });

        return redirect()->route('finance.payments.index')->with('success', 'Payment recorded successfully.');
    }

    private function generatePaymentNumber(int $schoolId): string
    {
        do {
            $number = 'PAY-'.date('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Payment::where('school_id', $schoolId)->where('payment_number', $number)->exists());

        return $number;
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['student', 'invoice', 'allocations', 'gatewayTransactions']);

        return Inertia::render('finance/payments/show', [
            'payment' => $payment,
        ]);
    }

    public function edit(Payment $payment)
    {
        $this->authorize('update', $payment);

        return Inertia::render('finance/payments/edit', [
            'payment' => $payment,
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $payment->update($request->validated());

        return redirect()->route('finance.payments.show', $payment)->with('success', 'Payment updated successfully.');
    }
}
