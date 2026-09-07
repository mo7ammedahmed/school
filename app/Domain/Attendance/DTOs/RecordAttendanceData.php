<?php

declare(strict_types=1);

namespace App\Domain\Attendance\DTOs;

final readonly class RecordAttendanceData
{
    public function __construct(
        public int $school_id,
        public int $attendance_session_id,
        public int $student_id,
        public string $status,
        public ?string $notes,
        public ?int $recorded_by,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'attendance_session_id' => ['required', 'integer', 'exists:attendance_sessions,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'recorded_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
