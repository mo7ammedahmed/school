<?php

namespace Database\Factories\Domain\People\Models;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => UserFactory::new(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'student_id_number' => fake()->unique()->numerify('STU-######'),
            'date_of_birth' => fake()->date('Y-m-d', '-10 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => 'Saudi',
            'national_id_number' => fake()->optional()->numerify('1##########'),
            'passport_number' => fake()->optional()->bothify('??######'),
            'address' => fake()->optional()->address(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'enrollment_date' => fake()->date('Y-m-d', '-1 year'),
            'status' => 'active',
            'medical_notes' => null,
        ];
    }
}
