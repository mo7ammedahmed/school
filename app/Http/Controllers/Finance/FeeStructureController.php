<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Domain\Finance\Models\FeeType;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeStructureController extends Controller
{
    public function index()
    {
        $feeStructures = FeeStructure::with(['feeType', 'gradeLevel'])->latest()->paginate(15);

        return Inertia::render('finance/fee-structures/index', [
            'feeStructures' => $feeStructures,
        ]);
    }

    public function create()
    {
        $gradeLevels = GradeLevel::orderBy('name_en')->get();
        $feeTypes = FeeType::orderBy('name')->get();

        return Inertia::render('finance/fee-structures/create', [
            'gradeLevels' => $gradeLevels,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fee_type_id' => 'required|exists:fee_types,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'amount' => 'required|numeric|min:0',
        ]);

        FeeStructure::create($validated);

        return redirect()->route('finance.fee-structures.index')->with('success', 'Fee structure created successfully.');
    }

    public function show(FeeStructure $feeStructure)
    {
        $feeStructure->load('feeType', 'gradeLevel');

        return Inertia::render('finance/fee-structures/show', [
            'feeStructure' => $feeStructure,
        ]);
    }

    public function edit(FeeStructure $feeStructure)
    {
        $gradeLevels = GradeLevel::orderBy('name_en')->get();
        $feeTypes = FeeType::orderBy('name')->get();

        return Inertia::render('finance/fee-structures/edit', [
            'feeStructure' => $feeStructure,
            'gradeLevels' => $gradeLevels,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function update(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fee_type_id' => 'required|exists:fee_types,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $feeStructure->update($validated);

        return redirect()->route('finance.fee-structures.index')->with('success', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $feeStructure->delete();

        return redirect()->route('finance.fee-structures.index')->with('success', 'Fee structure deleted successfully.');
    }
}
