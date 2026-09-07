<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Models\Refund;
use App\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function index()
    {
        $refunds = Refund::with(['invoice.student'])->latest()->paginate(15);
        return Inertia::render('finance/refunds/index', [
            'refunds' => $refunds,
        ]);
    }

    public function create()
    {
        $invoices = Invoice::with('student')->orderBy('created_at', 'desc')->get();
        return Inertia::render('finance/refunds/create', [
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'status' => 'required|in:pending,completed,rejected',
        ]);

        Refund::create($validated);

        return redirect()->route('finance.refunds.index')->with('success', 'Refund created successfully.');
    }

    public function show(Refund $refund)
    {
        $refund->load('invoice.student');
        return Inertia::render('finance/refunds/show', [
            'refund' => $refund,
        ]);
    }

    public function edit(Refund $refund)
    {
        $invoices = Invoice::with('student')->orderBy('created_at', 'desc')->get();
        return Inertia::render('finance/refunds/edit', [
            'refund' => $refund,
            'invoices' => $invoices,
        ]);
    }

    public function update(Request $request, Refund $refund): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'status' => 'required|in:pending,completed,rejected',
        ]);

        $refund->update($validated);

        return redirect()->route('finance.refunds.index')->with('success', 'Refund updated successfully.');
    }

    public function destroy(Refund $refund): RedirectResponse
    {
        $refund->delete();

        return redirect()->route('finance.refunds.index')->with('success', 'Refund deleted successfully.');
    }
}
