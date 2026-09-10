<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\People\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(): Response
    {
        $records = AttendanceRecord::where('school_id', session('school_id'))
            ->with(['student', 'attendanceSession'])
            ->latest()
            ->paginate(15);

        return inertia('attendance/index', ['attendances' => $records]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $students = Student::where('school_id', $schoolId)->orderBy('first_name')->get();
        $sessions = AttendanceSession::where('school_id', $schoolId)
            ->with(['offering.subject', 'section'])
            ->orderByDesc('session_date')
            ->get()
            ->map(fn (AttendanceSession $session) => [
                'id' => $session->id,
                'label' => sprintf(
                    '%s — %s · %s (%s)',
                    $session->section?->name ?? '—',
                    $session->offering?->subject?->name ?? '—',
                    $session->session_date?->toDateString(),
                    $session->status,
                ),
            ]);

        return inertia('attendance/create', ['students' => $students, 'sessions' => $sessions]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'attendance_session_id' => 'required|exists:attendance_sessions,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|string',
        ]);

        $record = AttendanceRecord::updateOrCreate(
            [
                'school_id' => $schoolId,
                'attendance_session_id' => $validated['attendance_session_id'],
                'student_id' => $validated['student_id'],
            ],
            [
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ],
        );

        return redirect()->route('attendance.show', $record)->with('success', 'Attendance marked successfully.');
    }

    public function show(AttendanceRecord $attendance): Response
    {
        $this->authorizeSchool($attendance);
        $attendance->load(['student', 'attendanceSession']);

        return inertia('attendance/show', ['attendance' => $attendance]);
    }

    public function edit(AttendanceRecord $attendance): Response
    {
        $this->authorizeSchool($attendance);
        $schoolId = session('school_id');
        $students = Student::where('school_id', $schoolId)->orderBy('first_name')->get();
        $sessions = AttendanceSession::where('school_id', $schoolId)
            ->with(['offering.subject', 'section'])
            ->orderByDesc('session_date')
            ->get()
            ->map(fn (AttendanceSession $session) => [
                'id' => $session->id,
                'label' => sprintf(
                    '%s — %s · %s (%s)',
                    $session->section?->name ?? '—',
                    $session->offering?->subject?->name ?? '—',
                    $session->session_date?->toDateString(),
                    $session->status,
                ),
            ]);

        return inertia('attendance/edit', [
            'attendance' => $attendance->load(['student', 'attendanceSession']),
            'students' => $students,
            'sessions' => $sessions,
        ]);
    }

    public function update(Request $request, AttendanceRecord $attendance): RedirectResponse
    {
        $this->authorizeSchool($attendance);
        $validated = $request->validate([
            'attendance_session_id' => 'required|exists:attendance_sessions,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|string',
        ]);

        $attendance->update([
            'attendance_session_id' => $validated['attendance_session_id'],
            'student_id' => $validated['student_id'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('attendance.show', $attendance)->with('success', 'Attendance updated successfully.');
    }

    public function destroy(AttendanceRecord $attendance): RedirectResponse
    {
        $this->authorizeSchool($attendance);
        $attendance->delete();

        return redirect()->route('attendance.index')->with('success', 'Attendance record deleted successfully.');
    }

    /** Legacy /attendance/reports/{type} endpoint. */
    public function reports(string $type): Response
    {
        return $this->index();
    }

    /** Legacy /attendance/record endpoint — marks one student against a session. */
    public function record(Request $request): RedirectResponse
    {
        return $this->store($request);
    }

    /** Legacy /my-attendance endpoint. */
    public function myAttendance(): Response
    {
        $student = Student::where('school_id', session('school_id'))
            ->where('user_id', auth()->id())
            ->first();

        $records = AttendanceRecord::where('school_id', session('school_id'))
            ->when($student, fn ($query) => $query->where('student_id', $student->id))
            ->with(['student', 'attendanceSession'])
            ->latest()
            ->paginate(15);

        return inertia('attendance/index', ['attendances' => $records]);
    }

    private function authorizeSchool(AttendanceRecord $record): void
    {
        if ((int) $record->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }
}
