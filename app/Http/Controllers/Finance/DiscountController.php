<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Models\Discount;
use App\Models\Student;
use App\Models\GradeLevel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::latest()->paginate(15);
        return Inertia::render('finance/discounts/index', [
            'discounts' => $discounts,
        ]);
    }

    public function create()
    {
        $students = Student::orderBy('name')->get();
        $gradeLevels = GradeLevel::orderBy('name')->get();
        return Inertia::render('finance/discounts/create', [
            'students' => $students,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Discount::create($validated);

        return redirect()->route('finance.discounts.index')->with('success', 'Discount created successfully.');
    }

    public function show(Discount $discount)
    {
        return Inertia::render('finance/discounts/show', [
            'discount' => $discount,
        ]);
    }

    public function edit(Discount $discount)
    {
        $students = Student::orderBy('name')->get();
        $gradeLevels = GradeLevel::orderBy('name')->get();
        return Inertia::render('finance/discounts/edit', [
            'discount' => $discount,
            'students' => $students,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $discount->update($validated);

        return redirect()->route('finance.discounts.index')->with('success', 'Discount updated successfully.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return redirect()->route('finance.discounts.index')->with('success', 'Discount deleted successfully.');
    }
}
