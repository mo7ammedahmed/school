<?php

namespace Database\Factories\Domain\Academics\Models;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GradeLevel> */
class GradeLevelFactory extends Factory
{
    protected $model = GradeLevel::class;

    public function definition(): array
    {
        $level = fake()->numberBetween(1, 12);

        return [
            'school_id' => School::factory(),
            'name_en' => "Grade {$level}",
            'name_ar' => "الصف {$level}",
            'code' => 'G'.$level,
            'level' => $level,
            'description' => null,
        ];
    }
}
