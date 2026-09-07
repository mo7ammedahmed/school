<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AssessmentController extends Controller
{
    public function index(): View
    {
        $assessments = Assessment::with('section')->latest()->paginate(15);
        return view('assessments.index', ['assessments' => $assessments]);
    }

    public function create(): View
    {
        $sections = Section::orderBy('name')->get();
        return view('assessments.create', ['sections' => $sections]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'assessment_type' => 'required|in:formative,summative,diagnostic,benchmark',
            'section_id' => 'required|exists:sections,id',
            'assessment_date' => 'required|date',
            'total_marks' => 'required|integer|min:1',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'description' => 'nullable|string',
        ]);

        $assessment = Assessment::create($validated);

        return redirect()->route('assessments.show', $assessment)->with('success', 'Assessment created successfully.');
    }

    public function show(Assessment $assessment): View
    {
        $assessment->load('section');
        return view('assessments.show', ['assessment' => $assessment]);
    }

    public function edit(Assessment $assessment): View
    {
        $sections = Section::orderBy('name')->get();
        return view('assessments.edit', ['assessment' => $assessment, 'sections' => $sections]);
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'assessment_type' => 'required|in:formative,summative,diagnostic,benchmark',
            'section_id' => 'required|exists:sections,id',
            'assessment_date' => 'required|date',
            'total_marks' => 'required|integer|min:1',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'description' => 'nullable|string',
        ]);

        $assessment->update($validated);

        return redirect()->route('assessments.show', $assessment)->with('success', 'Assessment updated successfully.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $assessment->delete();

        return redirect()->route('assessments.index')->with('success', 'Assessment deleted successfully.');
    }
}
