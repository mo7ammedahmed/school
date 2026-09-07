<?php

declare(strict_types=1);

namespace App\Domain\Academics\DTOs;

final readonly class CreateEnrollmentData
{
    public function __construct(
        public int $school_id,
        public int $student_id,
        public int $academic_year_id,
        public int $section_id,
        public string $enrollment_date,
        public string $status,
        public ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'enrollment_date' => ['required', 'date', 'date_format:Y-m-d'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
