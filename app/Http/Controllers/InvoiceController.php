<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\InvoiceLine;
use App\Domain\People\Models\Student;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('school_id');
        $query = Invoice::where('school_id', $schoolId)
            ->with('student')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->paginate(25);

        return Inertia::render('finance/invoices/index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        $students = Student::where('school_id', session('school_id'))->get();

        return Inertia::render('finance/invoices/create', [
            'students' => $students,
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $schoolId = session('school_id');
        $data = $request->validated();
        $data['school_id'] = $schoolId;

        $subtotal = (float) ($data['subtotal'] ?? 0);
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = $totalAmount;
        $data['amount_paid'] = 0;
        $data['balance_due'] = $totalAmount;
        $data['currency'] = 'SAR';
        $data['status'] = 'draft';

        DB::transaction(function () use ($schoolId, $data, $subtotal, $taxRate, $taxAmount, $discountAmount) {
            $invoice = Invoice::create($data);

            InvoiceLine::create([
                'school_id' => $schoolId,
                'invoice_id' => $invoice->id,
                'description' => 'Tuition Fee',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
            ]);
        });

        return redirect()->route('finance.invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['student', 'lines', 'payments.allocations', 'refunds']);

        return Inertia::render('finance/invoices/show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $students = Student::where('school_id', session('school_id'))->get();

        return Inertia::render('finance/invoices/edit', [
            'invoice' => $invoice,
            'students' => $students,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();

        $subtotal = (float) ($data['subtotal'] ?? 0);
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = $totalAmount;
        $data['balance_due'] = $totalAmount - $invoice->amount_paid;

        $invoice->update($data);

        return redirect()->route('finance.invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function issue(Invoice $invoice)
    {
        $this->authorize('issue', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be issued.');
        }

        $invoice->update([
            'status' => 'issued',
            'issue_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'Invoice issued successfully.');
    }
}
