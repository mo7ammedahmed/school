<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class CorrectAttendanceRecord
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(AttendanceRecord $record, string $newStatus, ?string $notes = null): AttendanceRecord
    {
        if ($record->school_id !== $this->school->id) {
            throw new Exception('Attendance record does not belong to this school.');
        }

        $session = $record->attendanceSession;
        if ($session->is_finalized) {
            throw new Exception('Cannot correct attendance for a finalized session.');
        }

        $validStatuses = ['present', 'absent', 'late', 'excused'];
        if (! in_array($newStatus, $validStatuses, true)) {
            throw new Exception('Invalid attendance status. Must be one of: '.implode(', ', $validStatuses).'.');
        }

        return DB::transaction(function () use ($record, $newStatus, $notes) {
            $record->update([
                'status' => $newStatus,
                'notes' => $notes ?? $record->notes,
            ]);

            return $record->fresh();
        });
    }
}
