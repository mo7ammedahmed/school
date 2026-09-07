<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\Subject;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::with('gradeLevel')->latest()->paginate(15);
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'credits' => 'nullable|numeric|min:0',
        ]);

        $subject = Subject::create($validated);

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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'grade_level_id' => 'required|exists:grade_levels,id',
            'credits' => 'nullable|numeric|min:0',
        ]);

        $subject->update($validated);

        return redirect()->route('subjects.show', $subject)->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
