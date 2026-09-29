<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentSettlementService;
use App\Domain\People\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentSettlementService $settlement) {}

    public function index(): InertiaResponse
    {
        $payments = Payment::query()
            ->where('school_id', $this->schoolId())
            ->with(['invoice:id,invoice_number', 'student:id,first_name,last_name'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Payment $payment) => $this->toRow($payment));

        return Inertia::render('finance/payments/index', [
            'payments' => $payments,
        ]);
    }

    /**
     * The queue of payments waiting on a human: bank transfers a guardian
     * confirmed from their payment link, plus anything recorded manually.
     */
    public function offline(): InertiaResponse
    {
        $payments = Payment::query()
            ->where('school_id', $this->schoolId())
            ->where('status', 'pending')
            ->with('invoice:id,invoice_number')
            ->latest()
            ->get()
            ->map(fn (Payment $payment) => $this->toRow($payment));

        return Inertia::render('finance/payments/offline', [
            'payments' => $payments,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('finance/payments/create', [
            'invoices' => $this->invoices(),
            'students' => $this->students(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', $this->studentRule()],
            'invoice_id' => ['required', 'integer', $this->invoiceRule()],
            'payment_number' => 'required|string|max:255|unique:payments,payment_number',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'required|in:cash,bank_transfer,moyasar,hyperpay,stripe',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,completed,paid,failed,refunded',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['currency'] ??= 'SAR';
        $validated['school_id'] = $this->schoolId();

        $payment = Payment::create($validated);

        // Recording a settled payment straight away still has to move the
        // invoice balance, not just the payment row.
        if (in_array($payment->status, ['paid', 'completed'], true)) {
            $this->settlement->settleManually($payment, $payment->reference_number, 'payment_recorded');
        }

        return redirect()->route('finance.payments.index')->with('success', 'Payment created successfully.');
    }

    public function show(Payment $payment): InertiaResponse
    {
        $this->ensureOwned($payment);

        $payment->load('invoice.student');

        return Inertia::render('finance/payments/show', [
            'payment' => $payment,
        ]);
    }

    public function edit(Payment $payment): InertiaResponse
    {
        $this->ensureOwned($payment);

        return Inertia::render('finance/payments/edit', [
            'payment' => $payment,
            'invoices' => $this->invoices(),
            'students' => $this->students(),
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensureOwned($payment);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', $this->studentRule()],
            'invoice_id' => ['required', 'integer', $this->invoiceRule()],
            'payment_number' => 'required|string|max:255|unique:payments,payment_number,'.$payment->id,
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'required|in:cash,bank_transfer,moyasar,hyperpay,stripe',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,completed,paid,failed,refunded',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['currency'] ??= 'SAR';

        $wasSettled = $payment->status === 'paid';
        $payment->update($validated);

        if (! $wasSettled && in_array($payment->status, ['paid', 'completed'], true)) {
            $this->settlement->settleManually($payment, $payment->reference_number, 'payment_recorded');
        }

        return redirect()->route('finance.payments.index')->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->ensureOwned($payment);

        $payment->delete();

        return redirect()->route('finance.payments.index')->with('success', 'Payment deleted successfully.');
    }

    /**
     * Confirmation screen for a payment that arrived outside a gateway.
     */
    public function review(Payment $payment): InertiaResponse
    {
        $this->ensureOwned($payment);

        $payment->load('invoice');

        return Inertia::render('finance/payments/return', [
            'payment' => array_merge($payment->toArray(), [
                'invoice_number' => $payment->invoice?->invoice_number,
            ]),
        ]);
    }

    /**
     * Confirm an offline payment. This settles it, updates the invoice and sends
     * the guardian a receipt.
     */
    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensureOwned($payment);

        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:255',
        ]);

        if ($payment->status === 'paid') {
            return back()->with('error', 'This payment is already confirmed.');
        }

        $this->settlement->settleManually($payment, $validated['reference_number'] ?? null);

        return redirect()
            ->route('finance.payments.offline')
            ->with('success', 'Payment confirmed. The invoice balance has been updated and a receipt was sent.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'reference_number' => $payment->reference_number,
            'status' => $payment->status,
            'payment_date' => optional($payment->payment_date)->toDateString(),
            'invoice' => $payment->invoice ? ['invoice_number' => $payment->invoice->invoice_number] : null,
            'student' => $payment->student ? [
                'first_name' => $payment->student->first_name,
                'last_name' => $payment->student->last_name,
            ] : null,
        ];
    }

    private function invoices(): Collection
    {
        return Invoice::query()
            ->where('school_id', $this->schoolId())
            ->orderByDesc('created_at')
            ->get(['id', 'invoice_number', 'student_id', 'balance_due', 'currency', 'status']);
    }

    private function students(): Collection
    {
        return Student::query()
            ->where('school_id', $this->schoolId())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'student_id_number']);
    }

    private function studentRule(): Exists
    {
        return Rule::exists('students', 'id')->where('school_id', $this->schoolId());
    }

    private function invoiceRule(): Exists
    {
        return Rule::exists('invoices', 'id')->where('school_id', $this->schoolId());
    }
}
