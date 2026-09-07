<?php

declare(strict_types=1);

namespace App\Domain\Attendance\DTOs;

final readonly class CreateAttendanceSessionData
{
    public function __construct(
        public int $school_id,
        public int $academic_year_id,
        public int $semester_id,
        public int $offering_id,
        public int $section_id,
        public int $teacher_id,
        public string $session_date,
        public string $start_time,
        public string $end_time,
        public string $status,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'teacher_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'session_date' => ['required', 'date', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'string', 'max:50'],
        ];
    }
}
