<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('invoice')->latest()->paginate(15);

        return Inertia::render('finance/payments/index', [
            'payments' => $payments,
        ]);
    }

    public function create()
    {
        $invoices = Invoice::orderBy('created_at', 'desc')->get();
        $students = Student::orderBy('first_name')->get();

        return Inertia::render('finance/payments/create', [
            'invoices' => $invoices,
            'students' => $students,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'payment_number' => 'required|string|max:255|unique:payments,payment_number',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'required|in:cash,bank_transfer,moyasar,hyperpay,stripe',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,completed,failed,refunded,paid',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['currency'] ??= 'SAR';
        $validated['school_id'] = $request->session()->get('school_id');

        Payment::create($validated);

        return redirect()->route('finance.payments.index')->with('success', 'Payment created successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load('invoice.student');

        return Inertia::render('finance/payments/show', [
            'payment' => $payment,
        ]);
    }

    public function edit(Payment $payment)
    {
        $invoices = Invoice::orderBy('created_at', 'desc')->get();
        $students = Student::orderBy('first_name')->get();

        return Inertia::render('finance/payments/edit', [
            'payment' => $payment,
            'invoices' => $invoices,
            'students' => $students,
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'payment_number' => 'required|string|max:255|unique:payments,payment_number,'.$payment->id,
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'required|in:cash,bank_transfer,moyasar,hyperpay,stripe',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,completed,failed,refunded,paid',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['currency'] ??= 'SAR';
        $validated['school_id'] = $request->session()->get('school_id');

        $payment->update($validated);

        return redirect()->route('finance.payments.index')->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('finance.payments.index')->with('success', 'Payment deleted successfully.');
    }
}
