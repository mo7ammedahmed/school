<?php

namespace Database\Factories;

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
            'role' => 'member',
            'permissions' => null,
            'is_active' => true,
            'last_login_at' => null,
        ];
    }
}
