<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Response;

class SemesterController extends Controller
{
    public function index(): Response
    {
        $semesters = Semester::where('school_id', $this->schoolId())
            ->with('academicYear')
            ->latest()
            ->paginate(15);

        return inertia('semesters/index', ['semesters' => $semesters]);
    }

    public function create(): Response
    {
        return inertia('semesters/create', ['academicYears' => $this->academicYears()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'academic_year_id' => ['required', 'integer', $this->academicYearRule()],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('semesters', 'code')->where('school_id', $this->schoolId()),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_current' => 'nullable|boolean',
        ]);

        $validated['school_id'] = $this->schoolId();
        $validated['is_current'] = $request->boolean('is_current');

        $semester = Semester::create($validated);

        return redirect()->route('semesters.show', $semester)->with('success', 'Semester created successfully.');
    }

    public function show(Semester $semester): Response
    {
        $this->authorizeSemester($semester);

        $semester->load('academicYear');

        return inertia('semesters/show', ['semester' => $semester]);
    }

    public function edit(Semester $semester): Response
    {
        $this->authorizeSemester($semester);

        return inertia('semesters/edit', [
            'semester' => $semester,
            'academicYears' => $this->academicYears(),
        ]);
    }

    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $this->authorizeSemester($semester);

        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'academic_year_id' => ['required', 'integer', $this->academicYearRule()],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('semesters', 'code')
                    ->where('school_id', $this->schoolId())
                    ->ignore($semester->id),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_current' => 'nullable|boolean',
        ]);

        $validated['is_current'] = $request->boolean('is_current');

        $semester->update($validated);

        return redirect()->route('semesters.show', $semester)->with('success', 'Semester updated successfully.');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        $this->authorizeSemester($semester);

        $semester->delete();

        return redirect()->route('semesters.index')->with('success', 'Semester deleted successfully.');
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    private function academicYears(): Collection
    {
        return AcademicYear::where('school_id', $this->schoolId())->orderByDesc('start_date')->get();
    }

    private function academicYearRule(): Exists
    {
        return Rule::exists('academic_years', 'id')->where('school_id', $this->schoolId());
    }

    private function schoolId(): int
    {
        return (int) session('school_id');
    }

    private function authorizeSemester(Semester $semester): void
    {
        abort_unless((int) $semester->school_id === $this->schoolId(), 403);
    }
}
