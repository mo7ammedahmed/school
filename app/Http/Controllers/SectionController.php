<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\Section;
use App\Models\GradeLevel;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class SectionController extends Controller
{
    public function index(): Response
    {
        $sections = Section::with(['gradeLevel', 'academicYear'])->latest()->paginate(15);
        return inertia('sections/index', ['sections' => $sections]);
    }

    public function create(): Response
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return inertia('sections/create', ['gradeLevels' => $gradeLevels, 'academicYears' => $academicYears]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'capacity' => 'required|integer|min:1',
        ]);

        $section = Section::create($validated);

        return redirect()->route('sections.show', $section)->with('success', 'Section created successfully.');
    }

    public function show(Section $section): Response
    {
        $section->load('gradeLevel', 'academicYear');
        return inertia('sections/show', ['section' => $section]);
    }

    public function edit(Section $section): Response
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return inertia('sections/edit', ['section' => $section, 'gradeLevels' => $gradeLevels, 'academicYears' => $academicYears]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'capacity' => 'required|integer|min:1',
        ]);

        $section->update($validated);

        return redirect()->route('sections.show', $section)->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return redirect()->route('sections.index')->with('success', 'Section deleted successfully.');
    }
}
