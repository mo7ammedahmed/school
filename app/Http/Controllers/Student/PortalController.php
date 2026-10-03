<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Models\Material;
use App\Domain\People\Models\Student;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function index(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $stats = [
            'attendance_rate' => $this->calculateAttendanceRate($student),
            'average_grade' => $this->calculateAverageGrade($student),
            'pending_assignments' => Assignment::whereHas('offering.section.students', fn ($q) => $q->where('students.id', $student->id))->count(),
            'outstanding_fees' => (float) Invoice::where('student_id', $student->id)->whereNotIn('status', ['paid', 'voided'])->sum('total_amount'),
        ];

        return inertia('student-portal/dashboard', [
            'student' => $student,
            'stats' => $stats,
        ]);
    }

    public function schedule(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $timetable = TimetableEntry::where('school_id', session('school_id'))
            ->where('is_published', true)
            ->whereHas('section.students', fn ($q) => $q->where('students.id', $student->id))
            ->with(['offering.subject', 'teacher.user', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return inertia('student-portal/schedule', [
            'student' => $student,
            'timetable' => $timetable,
        ]);
    }

    public function attendance(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $records = AttendanceRecord::where('student_id', $student->id)
            ->with('attendanceSession')
            ->latest()
            ->paginate(15);

        return inertia('student-portal/attendance', [
            'student' => $student,
            'records' => $records,
        ]);
    }

    public function grades(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $reportCards = ReportCard::where('student_id', $student->id)
            ->with('academicYear')
            ->latest()
            ->paginate(10);

        return inertia('student-portal/grades', [
            'student' => $student,
            'reportCards' => $reportCards,
        ]);
    }

    public function assignments(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $assignments = Assignment::whereHas('offering.section.students', fn ($q) => $q->where('students.id', $student->id))
            ->with(['offering.subject'])
            ->latest()
            ->paginate(15);

        return inertia('student-portal/assignments', [
            'student' => $student,
            'assignments' => $assignments,
        ]);
    }

    /**
     * The pupil's lessons: live sessions running now, and recorded ones.
     *
     * Live rows are the sessions of the pupil's own sections that are `live`
     * this moment; recorded rows are published materials of `kind` video or
     * recording from the same sections. The pages themselves are the only
     * student-facing surface for materials, which are otherwise staff documents.
     */
    public function lessons(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $enrolled = fn ($query) => $query->where('students.id', $student->id);

        $liveSessions = LiveSession::query()
            ->where('status', LiveSession::STATUS_LIVE)
            ->whereHas('offering.section.students', $enrolled)
            ->with(['offering.subject', 'offering.section'])
            ->latest('started_at')
            ->get();

        $recordings = Material::query()
            ->where('is_published', true)
            ->whereIn('kind', ['video', 'recording'])
            ->whereHas('offering.section.students', $enrolled)
            ->with(['offering.subject', 'offering.section'])
            ->latest()
            ->limit(60)
            ->get();

        return inertia('student-portal/lessons', [
            'student' => $student,
            'liveSessions' => $liveSessions,
            'recordings' => $recordings,
            'media' => [
                'configured' => (string) config('media.webrtc_url') !== '',
            ],
        ]);
    }

    public function fees(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $invoices = Invoice::where('student_id', $student->id)
            ->latest()
            ->paginate(15);

        return inertia('student-portal/fees', [
            'student' => $student,
            'invoices' => $invoices,
        ]);
    }

    private function calculateAttendanceRate(Student $student): float
    {
        $total = AttendanceRecord::where('student_id', $student->id)->count();
        if ($total === 0) {
            return 0.0;
        }

        $present = AttendanceRecord::where('student_id', $student->id)->where('status', 'present')->count();

        return round(($present / $total) * 100, 1);
    }

    private function calculateAverageGrade(Student $student): float
    {
        $reportCard = ReportCard::where('student_id', $student->id)->latest()->first();

        // `gpa` is a decimal column, so the driver hands it back as a string.
        return (float) ($reportCard?->gpa ?? 0.0);
    }
}
