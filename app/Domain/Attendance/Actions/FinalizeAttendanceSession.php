<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class FinalizeAttendanceSession
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(AttendanceSession $session): AttendanceSession
    {
        if ($session->school_id !== $this->school->id) {
            throw new Exception('Attendance session does not belong to this school.');
        }

        if ($session->is_finalized) {
            throw new Exception('Attendance session has already been finalized.');
        }

        $studentCount = $session->section->enrollments()
            ->where('academic_year_id', $session->academic_year_id)
            ->where('status', 'active')
            ->count();

        $recordCount = $session->records()->count();

        if ($recordCount < $studentCount) {
            throw new Exception("Attendance is incomplete. {$studentCount} students enrolled, but only {$recordCount} records captured.");
        }

        return DB::transaction(function () use ($session) {
            $session->update(['is_finalized' => true]);

            return $session->fresh();
        });
    }
}
