<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\Student;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class StudentController extends Controller
{
    private function schoolId(): int
    {
        return (int) session('school_id');
    }

    public function index(): Response
    {
        $students = Student::where('school_id', $this->schoolId())
            ->with('guardians')
            ->latest()
            ->paginate(15);

        return inertia('students/index', ['students' => $students]);
    }

    public function create(): Response
    {
        $guardians = Guardian::where('school_id', $this->schoolId())
            ->orderBy('first_name')
            ->get();

        return inertia('students/create', ['guardians' => $guardians]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'guardian_id' => 'nullable|exists:guardians,id',
            'enrollment_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,graduated,withdrawn',
        ]);

        $guardianId = $validated['guardian_id'] ?? null;
        unset($validated['guardian_id']);

        $validated['school_id'] = $this->schoolId();
        $validated['status'] ??= 'active';

        $student = Student::create($validated);

        if ($guardianId) {
            $student->guardians()->syncWithoutDetaching([
                $guardianId => ['school_id' => $this->schoolId(), 'is_primary' => true],
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Student added successfully.');
    }

    public function show(Student $student): Response
    {
        abort_unless((int) $student->school_id === $this->schoolId(), 403);

        $student->load('guardians');

        return inertia('students/show', ['student' => $student]);
    }

    public function edit(Student $student): Response
    {
        abort_unless((int) $student->school_id === $this->schoolId(), 403);

        $guardians = Guardian::where('school_id', $this->schoolId())
            ->orderBy('first_name')
            ->get();

        return inertia('students/edit', ['student' => $student, 'guardians' => $guardians]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === $this->schoolId(), 403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'guardian_id' => 'nullable|exists:guardians,id',
            'enrollment_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,graduated,withdrawn',
        ]);

        $guardianId = $validated['guardian_id'] ?? null;
        unset($validated['guardian_id']);

        $student->update($validated);

        if ($guardianId) {
            $student->guardians()->syncWithoutDetaching([
                $guardianId => ['school_id' => $this->schoolId(), 'is_primary' => true],
            ]);
        }

        return redirect()->route('students.show', $student)->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === $this->schoolId(), 403);

        $student->delete();

        return redirect()->route('students.index')->with('success', 'Student deleted successfully.');
    }
}
