<?php

declare(strict_types=1);

namespace App\Domain\Assessment\DTOs;

final readonly class CreateExamData
{
    public function __construct(
        public int $school_id,
        public int $academic_year_id,
        public int $semester_id,
        public int $offering_id,
        public string $name,
        public ?string $description,
        public string $exam_date,
        public string $start_time,
        public string $end_time,
        public ?int $room_id,
        public float $max_score,
        public bool $is_published,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'exam_date' => ['required', 'date', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
