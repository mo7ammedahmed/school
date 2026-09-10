<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Finance\Models\FeeType;
use App\Http\Requests\StoreFeeStructureRequest;
use App\Http\Requests\UpdateFeeStructureRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('school_id');
        $query = FeeStructure::where('school_id', $schoolId)
            ->with(['feeType', 'gradeLevel'])
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $feeStructures = $query->get();

        $feeStructures->transform(fn ($fee) => [
            'id' => $fee->id,
            'name' => $fee->name,
            'fee_type' => ['name' => $fee->feeType->name],
            'grade_level' => ['name' => $fee->gradeLevel->name],
            'amount' => $fee->amount,
        ]);

        return Inertia::render('finance/fee-structures/index', [
            'feeStructures' => $feeStructures,
        ]);
    }

    public function create()
    {
        $schoolId = session('school_id');
        $gradeLevels = GradeLevel::where('school_id', $schoolId)->get();
        $feeTypes = FeeType::where('school_id', $schoolId)->get();

        return Inertia::render('finance/fee-structures/create', [
            'gradeLevels' => $gradeLevels,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function store(StoreFeeStructureRequest $request)
    {
        $schoolId = session('school_id');
        $data = $request->validated();
        $data['school_id'] = $schoolId;

        FeeStructure::create($data);

        return redirect()->route('finance.fee-structures.index')->with('success', 'Fee structure created successfully.');
    }

    public function show(FeeStructure $feeStructure)
    {
        $this->authorize('view', $feeStructure);

        $feeStructure->load('feeType', 'gradeLevel');

        return Inertia::render('finance/fee-structures/show', [
            'feeStructure' => $feeStructure,
        ]);
    }

    public function edit(FeeStructure $feeStructure)
    {
        $this->authorize('update', $feeStructure);

        $schoolId = session('school_id');
        $gradeLevels = GradeLevel::where('school_id', $schoolId)->get();
        $feeTypes = FeeType::where('school_id', $schoolId)->get();

        return Inertia::render('finance/fee-structures/edit', [
            'feeStructure' => $feeStructure,
            'gradeLevels' => $gradeLevels,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function update(UpdateFeeStructureRequest $request, FeeStructure $feeStructure)
    {
        $this->authorize('update', $feeStructure);

        $feeStructure->update($request->validated());

        return redirect()->route('finance.fee-structures.show', $feeStructure)->with('success', 'Fee structure updated successfully.');
    }
}
