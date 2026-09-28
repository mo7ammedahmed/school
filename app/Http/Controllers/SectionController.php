<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(): Response
    {
        $sections = Section::where('school_id', $this->schoolId())
            ->with(['gradeLevel', 'academicYear'])
            ->latest()
            ->paginate(15);

        return inertia('sections/index', ['sections' => $sections]);
    }

    public function create(): Response
    {
        return inertia('sections/create', [
            'gradeLevels' => $this->gradeLevels(),
            'academicYears' => $this->academicYears(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'grade_level_id' => ['required', 'integer', $this->gradeLevelRule()],
            'academic_year_id' => ['required', 'integer', $this->academicYearRule()],
            'capacity' => 'required|integer|min:1',
        ]);

        // `school_id` is required by the table; the tenant scope supplies it.
        $validated['school_id'] = $this->schoolId();

        $section = Section::create($validated);

        return redirect()->route('sections.show', $section)->with('success', 'Section created successfully.');
    }

    public function show(Section $section): Response
    {
        $this->authorizeSection($section);

        $section->load('gradeLevel', 'academicYear');

        return inertia('sections/show', ['section' => $section]);
    }

    public function edit(Section $section): Response
    {
        $this->authorizeSection($section);

        return inertia('sections/edit', [
            'section' => $section,
            'gradeLevels' => $this->gradeLevels(),
            'academicYears' => $this->academicYears(),
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeSection($section);

        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'grade_level_id' => ['required', 'integer', $this->gradeLevelRule()],
            'academic_year_id' => ['required', 'integer', $this->academicYearRule()],
            'capacity' => 'required|integer|min:1',
        ]);

        $section->update($validated);

        return redirect()->route('sections.show', $section)->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $this->authorizeSection($section);

        $section->delete();

        return redirect()->route('sections.index')->with('success', 'Section deleted successfully.');
    }

    /**
     * @return Collection<int, GradeLevel>
     */
    private function gradeLevels(): Collection
    {
        return GradeLevel::where('school_id', $this->schoolId())->orderBy('level')->get();
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    private function academicYears(): Collection
    {
        return AcademicYear::where('school_id', $this->schoolId())->orderByDesc('start_date')->get();
    }

    private function gradeLevelRule(): Exists
    {
        return Rule::exists('grade_levels', 'id')->where('school_id', $this->schoolId());
    }

    private function academicYearRule(): Exists
    {
        return Rule::exists('academic_years', 'id')->where('school_id', $this->schoolId());
    }

    private function schoolId(): int
    {
        return (int) session('school_id');
    }

    private function authorizeSection(Section $section): void
    {
        abort_unless((int) $section->school_id === $this->schoolId(), 403);
    }
}
