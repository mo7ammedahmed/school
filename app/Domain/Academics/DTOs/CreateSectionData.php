<?php

declare(strict_types=1);

namespace App\Domain\Academics\DTOs;

final readonly class CreateSectionData
{
    public function __construct(
        public int $school_id,
        public int $academic_year_id,
        public int $grade_level_id,
        public string $name_ar,
        public string $name_en,
        public string $code,
        public ?int $homeroom_teacher_id,
        public ?int $capacity,
        public ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:teacher_profiles,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
