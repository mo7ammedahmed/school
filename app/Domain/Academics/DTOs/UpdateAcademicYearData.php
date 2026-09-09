<?php

declare(strict_types=1);

namespace App\Domain\Academics\DTOs;

final readonly class UpdateAcademicYearData
{
    public function __construct(
        public ?string $name_ar,
        public ?string $name_en,
        public ?string $start_date,
        public ?string $end_date,
        public ?bool $is_current,
    ) {}

    public static function rules(): array
    {
        return [
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }
}
