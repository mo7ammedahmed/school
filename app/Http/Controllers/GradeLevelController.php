<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class GradeLevelController extends Controller
{
    public function index(): Response
    {
        $gradeLevels = GradeLevel::orderBy('level')->paginate(15);
        return inertia('grade-levels/index', ['gradeLevels' => $gradeLevels]);
    }

    public function create(): Response
    {
        return inertia('grade-levels/create', []);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|unique:grade_levels,level',
            'description' => 'nullable|string',
        ]);

        $gradeLevel = GradeLevel::create($validated);

        return redirect()->route('grade-levels.show', $gradeLevel)->with('success', 'Grade level created successfully.');
    }

    public function show(GradeLevel $gradeLevel): Response
    {
        return inertia('grade-levels/show', ['gradeLevel' => $gradeLevel]);
    }

    public function edit(GradeLevel $gradeLevel): Response
    {
        return inertia('grade-levels/edit', ['gradeLevel' => $gradeLevel]);
    }

    public function update(Request $request, GradeLevel $gradeLevel): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|unique:grade_levels,level,' . $gradeLevel->id,
            'description' => 'nullable|string',
        ]);

        $gradeLevel->update($validated);

        return redirect()->route('grade-levels.show', $gradeLevel)->with('success', 'Grade level updated successfully.');
    }

    public function destroy(GradeLevel $gradeLevel): RedirectResponse
    {
        $gradeLevel->delete();

        return redirect()->route('grade-levels.index')->with('success', 'Grade level deleted successfully.');
    }
}
