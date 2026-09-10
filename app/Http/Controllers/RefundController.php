<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdateRefundRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('school_id');
        $query = Refund::where('school_id', $schoolId)
            ->with('payment')
            ->latest();

        if ($search = $request->input('search')) {
            $query->whereHas('payment', function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%");
            });
        }

        $refunds = $query->get();

        $refunds->transform(fn ($refund) => [
            'id' => $refund->id,
            'payment' => ['payment_number' => $refund->payment->payment_number],
            'amount' => $refund->amount,
            'reason' => $refund->reason,
            'status' => $refund->status,
        ]);

        return Inertia::render('finance/refunds/index', [
            'refunds' => $refunds,
        ]);
    }

    public function create()
    {
        $schoolId = session('school_id');
        $payments = Payment::where('school_id', $schoolId)
            ->where('status', '!=', 'refunded')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'amount' => $p->amount,
            ]);

        return Inertia::render('finance/refunds/create', [
            'payments' => $payments,
        ]);
    }

    public function store(StoreRefundRequest $request)
    {
        $schoolId = session('school_id');
        $data = $request->validated();
        $data['school_id'] = $schoolId;

        Refund::create($data);

        return redirect()->route('finance.refunds.index')->with('success', 'Refund processed successfully.');
    }

    public function show(Refund $refund)
    {
        $this->authorize('view', $refund);

        $refund->load('payment');

        return Inertia::render('finance/refunds/show', [
            'refund' => $refund,
        ]);
    }

    public function edit(Refund $refund)
    {
        $this->authorize('update', $refund);

        return Inertia::render('finance/refunds/edit', [
            'refund' => $refund,
        ]);
    }

    public function update(UpdateRefundRequest $request, Refund $refund)
    {
        $this->authorize('update', $refund);

        $refund->update($request->validated());

        return redirect()->route('finance.refunds.show', $refund)->with('success', 'Refund updated successfully.');
    }
}
