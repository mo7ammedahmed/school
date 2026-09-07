<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

class AcademicYearController extends Controller
{
    private function schoolId(): int
    {
        return (int) session('school_id');
    }

    public function index(): Response
    {
        $academicYears = AcademicYear::where('school_id', $this->schoolId())
            ->latest()
            ->paginate(15);

        return inertia('academic-years/index', ['academicYears' => $academicYears]);
    }

    public function create(): Response
    {
        return inertia('academic-years/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('academic_years', 'name')->where('school_id', $this->schoolId()),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['is_current']) {
                AcademicYear::where('school_id', $this->schoolId())
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            AcademicYear::create(array_merge($validated, ['school_id' => $this->schoolId()]));
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year created successfully.');
    }

    public function show(AcademicYear $academicYear): Response
    {
        abort_unless((int) $academicYear->school_id === $this->schoolId(), 403);

        return inertia('academic-years/show', ['academicYear' => $academicYear]);
    }

    public function edit(AcademicYear $academicYear): Response
    {
        abort_unless((int) $academicYear->school_id === $this->schoolId(), 403);

        return inertia('academic-years/edit', ['academicYear' => $academicYear]);
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        abort_unless((int) $academicYear->school_id === $this->schoolId(), 403);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('academic_years', 'name')
                    ->where('school_id', $this->schoolId())
                    ->ignore($academicYear->id),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'required|boolean',
        ]);

        DB::transaction(function () use ($academicYear, $validated) {
            if ($validated['is_current']) {
                AcademicYear::where('school_id', $this->schoolId())
                    ->where('is_current', true)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_current' => false]);
            }

            $academicYear->update($validated);
        });

        return redirect()->route('academic-years.show', $academicYear)->with('success', 'Academic year updated successfully.');
    }
}
