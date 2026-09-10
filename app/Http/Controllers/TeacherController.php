<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\People\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TeacherController extends Controller
{
    private function schoolId(): int
    {
        return (int) session('school_id');
    }

    public function index(): Response
    {
        $teachers = TeacherProfile::where('school_id', $this->schoolId())
            ->latest()
            ->paginate(15);

        return inertia('teachers/index', ['teachers' => $teachers]);
    }

    public function create(): Response
    {
        return inertia('teachers/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:teacher_profiles,email',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:255|unique:teacher_profiles,employee_id',
            'specialization' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'bio' => 'nullable|string',
        ]);

        $validated['school_id'] = $this->schoolId();

        TeacherProfile::create($validated);

        return redirect()->route('teachers.index')->with('success', 'Teacher added successfully.');
    }

    public function show(TeacherProfile $teacher): Response
    {
        abort_unless((int) $teacher->school_id === $this->schoolId(), 403);

        return inertia('teachers/show', ['teacher' => $teacher]);
    }

    public function edit(TeacherProfile $teacher): Response
    {
        abort_unless((int) $teacher->school_id === $this->schoolId(), 403);

        return inertia('teachers/edit', ['teacher' => $teacher]);
    }

    public function update(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        abort_unless((int) $teacher->school_id === $this->schoolId(), 403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:teacher_profiles,email,'.$teacher->id,
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:255|unique:teacher_profiles,employee_id,'.$teacher->id,
            'specialization' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'bio' => 'nullable|string',
        ]);

        $teacher->update($validated);

        return redirect()->route('teachers.show', $teacher)->with('success', 'Teacher updated successfully.');
    }
}
