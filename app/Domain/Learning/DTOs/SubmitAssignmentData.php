<?php

declare(strict_types=1);

namespace App\Domain\Learning\DTOs;

final readonly class SubmitAssignmentData
{
    public function __construct(
        public int $school_id,
        public int $assignment_id,
        public int $student_id,
        public string $content,
        public ?string $file_path,
        public ?string $file_type,
        public ?int $file_size,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'content' => ['required', 'string'],
            'file_path' => ['nullable', 'string', 'max:2000'],
            'file_type' => ['nullable', 'string', 'max:100'],
            'file_size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
