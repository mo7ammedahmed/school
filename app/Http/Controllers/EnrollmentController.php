<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Response;

/**
 * Enrolments are tenant rows, and every query here says so.
 *
 * The list had no `school_id` filter, the detail and edit screens resolved the
 * id straight off the primary key, and the form's student/section/year pickers
 * offered the whole platform. A school could therefore read and write another
 * school's enrolments, and enrol its own pupil into a stranger's section. Each
 * lookup now goes through `forSchool()`, the foreign-key rules are scoped with
 * `Rule::exists(...)`, and the model's tenant-aware binding answers 404 for an
 * id this school does not own.
 */
class EnrollmentController extends Controller
{
    public function index(): Response
    {
        $enrollments = Enrollment::forSchool($this->schoolId())
            ->with(['student', 'section', 'academicYear'])
            ->latest()
            ->paginate(15);

        return inertia('enrollments/index', ['enrollments' => $enrollments]);
    }

    public function create(): Response
    {
        $students = Student::forSchool($this->schoolId())->orderBy('first_name')->get();
        $sections = Section::forSchool($this->schoolId())->with('gradeLevel')->orderBy('name_en')->get();
        $academicYears = AcademicYear::forSchool($this->schoolId())->orderBy('name_en', 'desc')->get();

        return inertia('enrollments/create', ['students' => $students, 'sections' => $sections, 'academicYears' => $academicYears]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', $this->existsInSchool('students')],
            'section_id' => ['required', $this->existsInSchool('sections')],
            'academic_year_id' => ['required', $this->existsInSchool('academic_years')],
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,completed,withdrawn',
        ]);

        // The row belongs to the school the operator is working in; the form
        // does not send it, and the column is not nullable.
        $validated['school_id'] = $this->schoolId();

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
        $students = Student::forSchool($this->schoolId())->orderBy('first_name')->get();
        $sections = Section::forSchool($this->schoolId())->with('gradeLevel')->orderBy('name_en')->get();
        $academicYears = AcademicYear::forSchool($this->schoolId())->orderBy('name_en', 'desc')->get();

        return inertia('enrollments/edit', ['enrollment' => $enrollment, 'students' => $students, 'sections' => $sections, 'academicYears' => $academicYears]);
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', $this->existsInSchool('students')],
            'section_id' => ['required', $this->existsInSchool('sections')],
            'academic_year_id' => ['required', $this->existsInSchool('academic_years')],
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

    /**
     * An `exists` rule that cannot see another school's row.
     *
     * The unscoped form accepted any id on the platform, so an enrolment could
     * name a stranger's student, section or year and still validate.
     */
    private function existsInSchool(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('school_id', $this->schoolId());
    }
}
