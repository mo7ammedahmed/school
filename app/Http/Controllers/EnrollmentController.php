<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class EnrollmentController extends Controller
{
    public function index(): Response
    {
        $enrollments = Enrollment::with(['student', 'section', 'academicYear'])->latest()->paginate(15);
        return inertia('enrollments/index', ['enrollments' => $enrollments]);
    }

    public function create(): Response
    {
        $students = Student::orderBy('first_name')->get();
        $sections = Section::with('gradeLevel')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return inertia('enrollments/create', ['students' => $students, 'sections' => $sections, 'academicYears' => $academicYears]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,completed,withdrawn',
        ]);

        $enrollment = Enrollment::create($validated);

        return redirect()->route('enrollments.show', $enrollment)->with('success', 'Student enrolled successfully.');
    }

    public function show(Enrollment $enrollment): Response
    {
        $enrollment->load('student', 'section.gradeLevel', 'academicYear');
        return inertia('enrollments/show', ['enrollment' => $enrollment]);
    }

    public function edit(Enrollment $enrollment): Response
    {
        $students = Student::orderBy('first_name')->get();
        $sections = Section::with('gradeLevel')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return inertia('enrollments/edit', ['enrollment' => $enrollment, 'students' => $students, 'sections' => $sections, 'academicYears' => $academicYears]);
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,completed,withdrawn',
        ]);

        $enrollment->update($validated);

        return redirect()->route('enrollments.show', $enrollment)->with('success', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return redirect()->route('enrollments.index')->with('success', 'Enrollment deleted successfully.');
    }
}
