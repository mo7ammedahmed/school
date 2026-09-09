<?php

declare(strict_types=1);

namespace App\Domain\Academics\DTOs;

final readonly class CreateAcademicYearData
{
    public function __construct(
        public int $school_id,
        public string $name_ar,
        public string $name_en,
        public string $start_date,
        public string $end_date,
        public bool $is_current,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'is_current' => ['required', 'boolean'],
        ];
    }
}
