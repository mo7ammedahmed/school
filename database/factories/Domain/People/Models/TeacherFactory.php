<?php

namespace Database\Factories\Domain\People\Models;

use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = TeacherProfile::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => UserFactory::new(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'hire_date' => fake()->date('Y-m-d', '-5 years'),
            'qualification' => fake()->randomElement(['B.Ed', 'M.Ed', 'PhD']),
            'specialization' => fake()->randomElement(['Mathematics', 'Science', 'English', 'History']),
            'address' => fake()->optional()->address(),
            'status' => 'active',
        ];
    }
}
