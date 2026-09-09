<?php

namespace Database\Factories\Domain\Academics\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name_en' => fake()->randomElement(['2024-2025', '2025-2026']).' Academic Year',
            'name_ar' => fake()->randomElement(['٢٠٢٤-٢٠٢٥', '٢٠٢٥-٢٠٢٦']).' عام دراسي',
            'start_date' => fake()->date('Y-m-d', '-1 year'),
            'end_date' => fake()->date('Y-m-d', '+1 year'),
            'is_current' => false,
            'metadata' => null,
        ];
    }
}
