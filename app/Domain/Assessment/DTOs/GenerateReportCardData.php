<?php

declare(strict_types=1);

namespace App\Domain\Assessment\DTOs;

final readonly class GenerateReportCardData
{
    public function __construct(
        public int $school_id,
        public int $academic_year_id,
        public int $semester_id,
        public int $student_id,
        public ?int $published_by,
        public ?float $gpa,
        public ?array $grades,
        public ?array $attendance_summary,
        public ?string $comments,
        public ?string $published_at,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'published_by' => ['nullable', 'integer', 'exists:users,id'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'grades' => ['nullable', 'array'],
            'attendance_summary' => ['nullable', 'array'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'published_at' => ['nullable', 'date', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
