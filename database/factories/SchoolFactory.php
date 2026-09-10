<?php

namespace Database\Factories;

use App\Domain\Schools\Models\Organization;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name_ar' => fake()->company().' School',
            'name_en' => fake()->company().' School',
            'slug' => fake()->slug(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'city' => fake()->optional()->city(),
            'country' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'locale' => 'en',
            'currency' => 'SAR',
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#065f46',
            'secondary_color' => '#d97706',
            'metadata' => null,
        ];
    }
}
