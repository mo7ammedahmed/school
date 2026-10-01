<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Finance\Models\FeeType;
use App\Http\Requests\StoreFeeStructureRequest;
use App\Http\Requests\UpdateFeeStructureRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The fee a grade level pays for one kind of charge.
 *
 * Three things were wrong here at once. The list handed the page a `name` that
 * no column holds and reached through `feeType`/`gradeLevel` without checking
 * they were loaded, so a fee structure with no grade level took the whole screen
 * down with a 500. The detail and edit screens authorised through a policy that
 * called `hasPermissionTo('manage-fee-structures')`, a permission that does not
 * exist in the database — which threw before it could answer, so those two pages
 * were a 500 for every user rather than a 403. And the queries were unscoped, so
 * the list showed other schools' fees.
 */
class FeeStructureController extends Controller
{
    public function index(Request $request): Response
    {
        $feeStructures = FeeStructure::where('school_id', $this->schoolId())
            ->with(['feeType', 'gradeLevel'])
            ->when($request->input('search'), fn ($query, $search) => $query->where('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15);

        return Inertia::render('finance/fee-structures/index', [
            'feeStructures' => $feeStructures,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('finance/fee-structures/create', [
            'gradeLevels' => $this->gradeLevels(),
            'feeTypes' => $this->feeTypes(),
        ]);
    }

    public function store(StoreFeeStructureRequest $request): RedirectResponse
    {
        FeeStructure::create($request->validated() + ['school_id' => $this->schoolId()]);

        return redirect()->route('finance.fee-structures.index')->with('success', 'Fee structure created successfully.');
    }

    public function show(FeeStructure $feeStructure): Response
    {
        $feeStructure->load('feeType', 'gradeLevel');

        return Inertia::render('finance/fee-structures/show', [
            'feeStructure' => $feeStructure,
        ]);
    }

    public function edit(FeeStructure $feeStructure): Response
    {
        return Inertia::render('finance/fee-structures/edit', [
            'feeStructure' => $feeStructure,
            'gradeLevels' => $this->gradeLevels(),
            'feeTypes' => $this->feeTypes(),
        ]);
    }

    public function update(UpdateFeeStructureRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        $feeStructure->update($request->validated());

        return redirect()->route('finance.fee-structures.show', $feeStructure)->with('success', 'Fee structure updated successfully.');
    }

    /**
     * The options the operator may pick from: this school's only, so a crafted
     * request cannot attach another school's grade level or fee type.
     *
     * @return Collection<int, GradeLevel>
     */
    private function gradeLevels(): Collection
    {
        return GradeLevel::where('school_id', $this->schoolId())->orderBy('name_en')->get();
    }

    /** @return Collection<int, FeeType> */
    private function feeTypes(): Collection
    {
        return FeeType::where('school_id', $this->schoolId())->orderBy('name')->get();
    }
}
