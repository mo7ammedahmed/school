<?php

declare(strict_types=1);

namespace App\Domain\Assessment\DTOs;

final readonly class RecordExamResultData
{
    public function __construct(
        public int $school_id,
        public int $exam_id,
        public int $student_id,
        public float $score,
        public ?string $notes,
        public ?int $graded_by,
        public ?string $graded_at,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'score' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'graded_by' => ['nullable', 'integer', 'exists:users,id'],
            'graded_at' => ['nullable', 'date', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
