<?php

namespace Database\Factories\Domain\Academics\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Semester;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Semester> */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name_en' => 'Semester '.fake()->unique()->numberBetween(1, 3),
            'name_ar' => 'الفصل الدراسي',
            'code' => strtoupper(fake()->unique()->bothify('SEM#')),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+4 months')->format('Y-m-d'),
            'is_current' => false,
        ];
    }
}
