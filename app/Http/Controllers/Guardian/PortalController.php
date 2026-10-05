<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guardian;

use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Learning\Models\Assignment;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function index(Request $request)
    {
        $guardian = Guardian::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $children = $guardian->students()->get();

        $childrenSummary = $children->map(fn (Student $child) => [
            'id' => $child->id,
            'name' => $child->first_name.' '.$child->last_name,
            'attendance_rate' => $this->calculateAttendanceRate($child),
            'average_grade' => $this->calculateAverageGrade($child),
            'outstanding_fees' => (float) Invoice::where('status', '!=', 'draft')->where('student_id', $child->id)->whereNotIn('status', ['paid', 'voided'])->sum('balance_due'),
        ]);

        return inertia('guardian-portal/dashboard', [
            'guardian' => $guardian->only(['id', 'first_name', 'last_name']) + ['name' => trim($guardian->first_name.' '.$guardian->last_name)],
            'children' => $childrenSummary,
        ]);
    }

    public function children(Request $request)
    {
        $guardian = Guardian::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        $children = $guardian->students()->get();

        return inertia('guardian-portal/children', [
            'guardian' => $guardian->only(['id', 'first_name', 'last_name']) + ['name' => trim($guardian->first_name.' '.$guardian->last_name)],
            'children' => $children->map(fn (Student $child): array => $child->only(['id', 'first_name', 'last_name', 'date_of_birth', 'gender'])),
        ]);
    }

    public function childSchedule(Request $request, Student $child)
    {
        $this->authorizeChild($request, $child);

        $timetable = TimetableEntry::where('school_id', session('school_id'))
            ->where('is_published', true)
            ->whereHas('section.students', fn ($q) => $q->where('students.id', $child->id))
            ->with(['offering.subject', 'teacher:id,user_id,first_name,last_name', 'teacher.user:id,name', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return inertia('guardian-portal/child-schedule', [
            'child' => $child->only(['id', 'first_name', 'last_name']),
            'timetable' => $timetable,
        ]);
    }

    public function childAttendance(Request $request, Student $child)
    {
        $this->authorizeChild($request, $child);

        $records = AttendanceRecord::where('student_id', $child->id)
            ->with('attendanceSession')
            ->latest()
            ->paginate(15);

        return inertia('guardian-portal/child-attendance', [
            'child' => $child->only(['id', 'first_name', 'last_name']),
            'records' => $records,
        ]);
    }

    public function childGrades(Request $request, Student $child)
    {
        $this->authorizeChild($request, $child);

        $reportCards = ReportCard::whereNotNull('published_at')->where('published_at', '<=', now())->where('student_id', $child->id)
            ->with('academicYear')
            ->latest()
            ->paginate(10);

        return inertia('guardian-portal/child-grades', [
            'child' => $child->only(['id', 'first_name', 'last_name']),
            'reportCards' => $reportCards,
        ]);
    }

    public function childAssignments(Request $request, Student $child)
    {
        $this->authorizeChild($request, $child);

        $assignments = Assignment::where('is_published', true)->whereHas('offering.section.students', fn ($q) => $q->where('students.id', $child->id))
            ->with(['offering.subject'])
            ->latest()
            ->paginate(15);

        return inertia('guardian-portal/child-assignments', [
            'child' => $child->only(['id', 'first_name', 'last_name']),
            'assignments' => $assignments,
        ]);
    }

    public function childFees(Request $request, Student $child)
    {
        $this->authorizeChild($request, $child);

        $invoices = Invoice::where('status', '!=', 'draft')->where('student_id', $child->id)
            ->latest()
            ->paginate(15);

        return inertia('guardian-portal/child-fees', [
            'child' => $child->only(['id', 'first_name', 'last_name']),
            'invoices' => $invoices,
        ]);
    }

    /**
     * A guardian may only open their own children's records.
     *
     * Deliberately not the school check the other controllers use: being inside
     * the right school is not enough to read a specific child here, the guardian
     * relationship is what grants access.
     */
    private function authorizeChild(Request $request, Student $child): void
    {
        $guardian = Guardian::where('user_id', $request->user()->id)
            ->where('school_id', session('school_id'))
            ->firstOrFail();

        if (! $guardian->students()->where('students.id', $child->id)->exists()) {
            abort(403, 'Unauthorized access to child records.');
        }
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
        $reportCard = ReportCard::whereNotNull('published_at')->where('published_at', '<=', now())->where('student_id', $student->id)->latest()->first();

        // `gpa` is a decimal column, so the driver hands it back as a string.
        return (float) ($reportCard?->gpa ?? 0.0);
    }
}
