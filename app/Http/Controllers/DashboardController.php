<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Academics\Models\Section;
use App\Domain\Finance\Models\Payment;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Finance\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $schoolId = session('school_id');

        // Initialize stats with default values
        $stats = [
            'total_students' => 0,
            'total_teachers' => 0,
            'total_classes' => 0,
            'total_revenue' => 0,
            'attendance_rate' => 0.0,
            'pending_payments' => 0,
        ];

        // Only fetch stats if we have a valid school ID
        if ($schoolId) {
            $stats = [
                'total_students' => Student::where('school_id', $schoolId)->count(),
                'total_teachers' => TeacherProfile::where('school_id', $schoolId)->count(),
                'total_classes' => Section::where('school_id', $schoolId)->count(),
                'total_revenue' => Payment::where('school_id', $schoolId)->where('status', 'completed')->sum('amount'),
                'attendance_rate' => $this->calculateAttendanceRate($schoolId),
                'pending_payments' => Invoice::where('school_id', $schoolId)->whereNotIn('status', ['paid', 'voided', 'draft'])->count(),
            ];
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
