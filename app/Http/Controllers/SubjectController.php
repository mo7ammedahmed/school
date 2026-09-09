<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::select('id', 'code', 'grade_level_id', 'name_ar', 'name_en')->with('gradeLevel')->latest()->paginate(15);

        return inertia('subjects/index', ['subjects' => $subjects]);
    }

    public function create(): Response
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return inertia('subjects/create', ['gradeLevels' => $gradeLevels]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'credits' => 'nullable|numeric|min:0',
        ]);

        $subject = Subject::create([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'code' => $validated['code'],
            'grade_level_id' => $validated['grade_level_id'],
            'credits' => $validated['credits'],
        ]);

        return redirect()->route('subjects.show', $subject)->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject): Response
    {
        $subject->load('gradeLevel');

        return inertia('subjects/show', ['subject' => $subject]);
    }

    public function edit(Subject $subject): Response
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return inertia('subjects/edit', ['subject' => $subject, 'gradeLevels' => $gradeLevels]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,'.$subject->id,
            'grade_level_id' => 'required|exists:grade_levels,id',
            'credits' => 'nullable|numeric|min:0',
        ]);

        $subject->update([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'code' => $validated['code'],
            'grade_level_id' => $validated['grade_level_id'],
            'credits' => $validated['credits'],
        ]);

        return redirect()->route('subjects.show', $subject)->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
