<?php

namespace Database\Factories\Domain\Identity\Models;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserMembershipFactory extends Factory
{
    protected $model = UserMembership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'school_id' => School::factory(),
            'role' => fake()->randomElement(['admin', 'teacher', 'student', 'guardian']),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
