<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Section;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('school_id');

        $stats = [];

        // A hidden tile is not a data boundary: only send statistics for
        // modules the caller can open.
        if ($schoolId) {
            $user = $request->user();

            if ($user->can('manage-students')) {
                $stats['total_students'] = Student::where('school_id', $schoolId)->count();
            }
            if ($user->can('manage-teachers')) {
                $stats['total_teachers'] = TeacherProfile::where('school_id', $schoolId)->count();
            }
            if ($user->can('manage-sections')) {
                $stats['total_classes'] = Section::where('school_id', $schoolId)->count();
            }
            if ($user->can('manage-payments')) {
                $stats['total_revenue'] = Payment::where('school_id', $schoolId)->where('status', 'completed')->sum('amount');
            }
            if ($user->can('manage-attendance')) {
                $stats['attendance_rate'] = $this->calculateAttendanceRate($schoolId);
            }
            if ($user->can('manage-invoices')) {
                $stats['pending_payments'] = Invoice::where('school_id', $schoolId)->whereNotIn('status', ['paid', 'voided', 'draft'])->count();
            }
        }

        return Inertia::render('dashboard', [
            'stats' => $stats,
        ]);
    }

    private function calculateAttendanceRate(int $schoolId): float
    {
        $total = AttendanceRecord::where('school_id', $schoolId)->count();
        if ($total === 0) {
            return 0.0;
        }

        $present = AttendanceRecord::where('school_id', $schoolId)->where('status', 'present')->count();

        return round(($present / $total) * 100, 1);
    }
}
