<?php

namespace Database\Factories;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-2 years', 'now');

        return [
            'school_id' => School::factory(),
            'name_en' => $start->format('Y').'-'.((int) $start->format('Y') + 1),
            'name_ar' => $start->format('Y').'-'.((int) $start->format('Y') + 1),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $start->modify('+9 months')->format('Y-m-d'),
            'is_current' => false,
        ];
    }
}
