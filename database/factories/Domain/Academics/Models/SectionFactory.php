<?php

namespace Database\Factories\Domain\Academics\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Section> */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'name_en' => 'Section '.fake()->unique()->randomLetter(),
            'name_ar' => 'شعبة',
            'code' => strtoupper(fake()->unique()->bothify('S##')),
            'capacity' => 30,
            'current_count' => 0,
        ];
    }
}
