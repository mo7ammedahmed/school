<?php

namespace Database\Factories\Domain\Academics\Models;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Subject;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Mathematics', 'English', 'Science', 'Arabic', 'History', 'Geography',
        ]);

        return [
            'school_id' => School::factory(),
            'name_en' => $name,
            'name_ar' => $name,
            'code' => strtoupper(substr($name, 0, 4)).fake()->unique()->numberBetween(1, 999),
            'subject_type' => 'academic',
            'grade_level_id' => GradeLevel::factory(),
        ];
    }
}
