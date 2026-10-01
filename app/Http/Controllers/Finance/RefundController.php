<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Money going back to a guardian.
 *
 * The form asks which invoice is being refunded, but the table also requires a
 * `payment_id` and a `currency` — neither of which was set, so creating a refund
 * failed on a NOT NULL constraint every single time. A refund is money returning
 * from a payment, so the payment is resolved from the invoice: the one named on
 * the form when it is given, otherwise the invoice's own payment. An invoice with
 * no payment has nothing to refund, and that is now a message on the field rather
 * than a 500.
 */
class RefundController extends Controller
{
    public function index(): Response
    {
        $refunds = Refund::where('school_id', $this->schoolId())
            ->with(['invoice.student'])
            ->latest()
            ->paginate(15);

        return Inertia::render('finance/refunds/index', [
            'refunds' => $refunds,
        ]);
    }

    public function create(): Response
    {
        $invoices = Invoice::where('school_id', $this->schoolId())
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('finance/refunds/create', [
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Refund::create($validated + ['school_id' => $this->schoolId()]);

        return redirect()->route('finance.refunds.index')->with('success', 'Refund created successfully.');
    }

    public function show(Refund $refund): Response
    {
        $refund->load('invoice.student');

        return Inertia::render('finance/refunds/show', [
            'refund' => $refund,
        ]);
    }

    public function edit(Refund $refund): Response
    {
        $invoices = Invoice::where('school_id', $this->schoolId())
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('finance/refunds/edit', [
            'refund' => $refund,
            'invoices' => $invoices,
        ]);
    }

    public function update(Request $request, Refund $refund): RedirectResponse
    {
        $refund->update($this->validated($request));

        return redirect()->route('finance.refunds.index')->with('success', 'Refund updated successfully.');
    }

    public function destroy(Refund $refund): RedirectResponse
    {
        $refund->delete();

        return redirect()->route('finance.refunds.index')->with('success', 'Refund deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $schoolId = $this->schoolId();

        $validated = $request->validate([
            'invoice_id' => ['required', Rule::exists('invoices', 'id')->where('school_id', $schoolId)],
            'payment_id' => ['nullable', Rule::exists('payments', 'id')->where('school_id', $schoolId)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:2000', 'required_without:reason_ar'],
            'reason_ar' => ['nullable', 'string', 'max:2000', 'required_without:reason'],
            'status' => ['required', 'in:pending,completed,rejected'],
        ]);

        $invoice = Invoice::where('school_id', $schoolId)->findOrFail($validated['invoice_id']);

        // A refund returns money that a payment brought in, so there has to be a
        // payment to point at — the one named, or the invoice's own.
        $payment = isset($validated['payment_id'])
            ? Payment::where('school_id', $schoolId)->findOrFail($validated['payment_id'])
            : Payment::where('school_id', $schoolId)->where('invoice_id', $invoice->id)->latest('payment_date')->first();

        if (! $payment instanceof Payment) {
            throw ValidationException::withMessages([
                'invoice_id' => 'This invoice has no payment recorded, so there is nothing to refund.',
            ]);
        }

        $validated['payment_id'] = $payment->id;
        // The table requires a currency; the invoice already knows which one the
        // charge was made in, so the refund cannot disagree with it.
        $validated['currency'] = $invoice->currency ?? $payment->currency;

        return $validated;
    }
}
