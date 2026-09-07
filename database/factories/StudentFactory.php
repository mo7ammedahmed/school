<?php

namespace Database\Factories;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'student_id_number' => strtoupper(fake()->bothify('??#####')),
            'date_of_birth' => fake()->date('Y-m-d', '-5 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => fake()->optional()->country(),
            'national_id_number' => fake()->optional()->ssn(),
            'passport_number' => fake()->optional()->bothify('?########'),
            'address' => fake()->optional()->address(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'enrollment_date' => fake()->date('Y-m-d'),
            'status' => 'active',
            'medical_notes' => null,
            'metadata' => null,
        ];
    }
}
