<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Domain\Finance\Models\Discount;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('school_id');
        $query = Discount::where('school_id', $schoolId)
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $discounts = $query->get();

        return Inertia::render('finance/discounts/index', [
            'discounts' => $discounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('finance/discounts/create');
    }

    public function store(StoreDiscountRequest $request)
    {
        $schoolId = session('school_id');
        $data = $request->validated();
        $data['school_id'] = $schoolId;

        Discount::create($data);

        return redirect()->route('finance.discounts.index')->with('success', 'Discount created successfully.');
    }

    public function show(Discount $discount)
    {
        $this->authorize('view', $discount);

        return Inertia::render('finance/discounts/show', [
            'discount' => $discount,
        ]);
    }

    public function edit(Discount $discount)
    {
        $this->authorize('update', $discount);

        return Inertia::render('finance/discounts/edit', [
            'discount' => $discount,
        ]);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount)
    {
        $this->authorize('update', $discount);

        $discount->update($request->validated());

        return redirect()->route('finance.discounts.show', $discount)->with('success', 'Discount updated successfully.');
    }
}
