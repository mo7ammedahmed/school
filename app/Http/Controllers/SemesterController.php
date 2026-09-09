<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SemesterController extends Controller
{
    public function index(): Response
    {
        $semesters = Semester::with('academicYear')->latest()->paginate(15);

        return inertia('semesters/index', ['semesters' => $semesters]);
    }

    public function create(): Response
    {
        return inertia('semesters/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:semesters,code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_current' => 'required|boolean',
        ]);

        $validated['is_current'] = $request->has('is_current');

        $semester = Semester::create($validated);

        return redirect()->route('semesters.show', $semester)->with('success', 'Semester created successfully.');
    }

    public function show(Semester $semester): Response
    {
        $semester->load('academicYear');

        return inertia('semesters/show', ['semester' => $semester]);
    }

    public function edit(Semester $semester): Response
    {
        return inertia('semesters/edit', ['semester' => $semester]);
    }

    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:semesters,code,'.$semester->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_current' => 'required|boolean',
        ]);

        $validated['is_current'] = $request->has('is_current');

        $semester->update($validated);

        return redirect()->route('semesters.show', $semester)->with('success', 'Semester updated successfully.');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        $semester->delete();

        return redirect()->route('semesters.index')->with('success', 'Semester deleted successfully.');
    }
}
