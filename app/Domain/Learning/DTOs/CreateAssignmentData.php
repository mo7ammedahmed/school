<?php

declare(strict_types=1);

namespace App\Domain\Learning\DTOs;

final readonly class CreateAssignmentData
{
    public function __construct(
        public int $school_id,
        public int $offering_id,
        public string $title,
        public ?string $description,
        public ?string $instructions,
        public string $due_date,
        public float $max_score,
        public bool $allow_late_submission,
        public bool $is_published,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['required', 'date', 'date_format:Y-m-d'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'allow_late_submission' => ['required', 'boolean'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
