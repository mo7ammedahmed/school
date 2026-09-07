<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Models\Invoice;
use App\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('student')->latest()->paginate(15);
        return Inertia::render('finance/invoices/index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        $students = Student::orderBy('first_name')->get();
        return Inertia::render('finance/invoices/create', [
            'students' => $students,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',
            'issue_date' => 'nullable|date',
            'due_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $subtotal = (float) ($validated['subtotal'] ?? 0);
        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $validated['tax_amount'] = $taxAmount;
        $validated['total_amount'] = $totalAmount;
        $validated['amount_paid'] = 0;
        $validated['balance_due'] = $totalAmount;
        $validated['currency'] = 'SAR';
        $validated['status'] = 'draft';
        $validated['school_id'] = $request->session()->get('school_id');

        Invoice::create($validated);

        return redirect()->route('finance.invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('student', 'lines', 'payments');
        return Inertia::render('finance/invoices/show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $students = Student::orderBy('first_name')->get();
        return Inertia::render('finance/invoices/edit', [
            'invoice' => $invoice,
            'students' => $students,
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'issued' || $invoice->status === 'paid') {
            abort(403, 'Issued or paid invoices cannot be edited.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number,' . $invoice->id,
            'issue_date' => 'nullable|date',
            'due_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,issued,paid,overdue,void',
            'notes' => 'nullable|string',
        ]);

        $subtotal = (float) ($validated['subtotal'] ?? 0);
        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $validated['tax_amount'] = $taxAmount;
        $validated['total_amount'] = $totalAmount;
        $validated['balance_due'] = $totalAmount - $invoice->amount_paid;

        $invoice->update($validated);

        return redirect()->route('finance.invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('finance.invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}
