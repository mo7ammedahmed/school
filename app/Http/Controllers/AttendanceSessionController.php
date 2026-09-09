<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceSessionController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $sessions = AttendanceSession::where('school_id', $schoolId)
            ->with(['offering.subject', 'section', 'teacher', 'semester', 'academicYear'])
            ->latest()
            ->paginate(15);

        return inertia('attendance-sessions/index', ['sessions' => $sessions]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();
        $teachers = TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')->get();
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $semesters = Semester::where('school_id', $schoolId)->orderBy('name_en')->get();
        $academicYears = AcademicYear::where('school_id', $schoolId)->orderBy('name_en', 'desc')->get();

        return inertia('attendance-sessions/create', [
            'sections' => $sections,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'semesters' => $semesters,
            'academicYears' => $academicYears,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'teacher_id' => 'nullable|exists:teacher_profiles,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'session_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'is_finalized' => 'nullable|boolean',
        ]);

        $offering = $this->resolveOffering(
            $validated['offering_id'] ?? null,
            (int) ($validated['subject_id'] ?? 0),
            (int) ($validated['teacher_id'] ?? 0),
            (int) $validated['section_id'],
        );

        $session = AttendanceSession::create([
            'school_id' => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'semester_id' => $validated['semester_id'],
            'offering_id' => $offering->id,
            'section_id' => $offering->section_id,
            'teacher_id' => $offering->teacher_id,
            'session_date' => $validated['session_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'is_finalized' => $request->boolean('is_finalized'),
        ]);

        return redirect()->route('attendance-sessions.show', $session)->with('success', 'Attendance session created successfully.');
    }

    public function show(AttendanceSession $session): Response
    {
        $this->authorizeSchool($session);
        $session->load(['offering.subject', 'section', 'teacher', 'semester', 'academicYear', 'records.student']);

        return inertia('attendance-sessions/show', ['session' => $session]);
    }

    public function edit(AttendanceSession $session): Response
    {
        $this->authorizeSchool($session);
        $schoolId = session('school_id');
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();
        $teachers = TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')->get();
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $semesters = Semester::where('school_id', $schoolId)->orderBy('name_en')->get();
        $academicYears = AcademicYear::where('school_id', $schoolId)->orderBy('name_en', 'desc')->get();

        return inertia('attendance-sessions/edit', [
            'session' => $session->load(['offering.subject', 'section', 'teacher', 'semester', 'academicYear']),
            'sections' => $sections,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'semesters' => $semesters,
            'academicYears' => $academicYears,
        ]);
    }

    public function update(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSchool($session);
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'teacher_id' => 'nullable|exists:teacher_profiles,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'session_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'is_finalized' => 'nullable|boolean',
        ]);

        $offering = $this->resolveOffering(
            $validated['offering_id'] ?? null,
            (int) ($validated['subject_id'] ?? 0),
            (int) ($validated['teacher_id'] ?? 0),
            (int) $validated['section_id'],
        );

        $session->update([
            'academic_year_id' => $validated['academic_year_id'],
            'semester_id' => $validated['semester_id'],
            'offering_id' => $offering->id,
            'section_id' => $offering->section_id,
            'teacher_id' => $offering->teacher_id,
            'session_date' => $validated['session_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'is_finalized' => $request->boolean('is_finalized'),
        ]);

        return redirect()->route('attendance-sessions.show', $session)->with('success', 'Attendance session updated successfully.');
    }

    public function destroy(AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSchool($session);
        $session->delete();

        return redirect()->route('attendance-sessions.index')->with('success', 'Attendance session deleted successfully.');
    }

    private function authorizeSchool(AttendanceSession $session): void
    {
        if ((int) $session->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }

    private function resolveOffering(?int $offeringId, int $subjectId, int $teacherId, int $sectionId): Offering
    {
        $query = Offering::where('school_id', session('school_id'))->where('section_id', $sectionId);

        $offering = $offeringId ? $query->find($offeringId) : null;

        if (! $offering && $subjectId) {
            $query = $query->where('subject_id', $subjectId);
            $offering = $teacherId
                ? (clone $query)->where('teacher_id', $teacherId)->first() ?? (clone $query)->first()
                : (clone $query)->first();
        }

        if (! $offering) {
            throw ValidationException::withMessages([
                'subject_id' => 'No active offering exists for this subject and section.',
            ]);
        }

        return $offering;
    }
}
