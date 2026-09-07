<?php

declare(strict_types=1);

namespace App\Domain\Learning\DTOs;

final readonly class CreateQuizData
{
    public function __construct(
        public int $school_id,
        public int $offering_id,
        public string $title,
        public ?string $description,
        public array $questions,
        public ?int $time_limit_minutes,
        public float $max_score,
        public bool $is_published,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'questions' => ['required', 'array', 'min:1'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
