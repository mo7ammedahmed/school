<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(): Response
    {
        $classrooms = Classroom::with(['section', 'teacher'])->latest()->paginate(15);

        return inertia('classrooms/index', ['classrooms' => $classrooms]);
    }

    public function create(): Response
    {
        $sections = Section::orderBy('name_en')->get();
        $teachers = Teacher::orderBy('first_name')->get();

        return inertia('classrooms/create', ['sections' => $sections, 'teachers' => $teachers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'teacher_id' => 'required|exists:teachers,id',
            'capacity' => 'required|integer|min:1',
            'room_number' => 'nullable|string|max:50',
            'building' => 'nullable|string|max:255',
        ]);

        $classroom = Classroom::create($validated);

        return redirect()->route('classrooms.show', $classroom)->with('success', 'Classroom created successfully.');
    }

    public function show(Classroom $classroom): Response
    {
        $classroom->load('section', 'teacher');

        return inertia('classrooms/show', ['classroom' => $classroom]);
    }

    public function edit(Classroom $classroom): Response
    {
        $sections = Section::orderBy('name_en')->get();
        $teachers = Teacher::orderBy('first_name')->get();

        return inertia('classrooms/edit', ['classroom' => $classroom, 'sections' => $sections, 'teachers' => $teachers]);
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'teacher_id' => 'required|exists:teachers,id',
            'capacity' => 'required|integer|min:1',
            'room_number' => 'nullable|string|max:50',
            'building' => 'nullable|string|max:255',
        ]);

        $classroom->update($validated);

        return redirect()->route('classrooms.show', $classroom)->with('success', 'Classroom updated successfully.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'Classroom deleted successfully.');
    }
}
