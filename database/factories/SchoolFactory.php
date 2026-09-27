<?php

namespace Database\Factories;

use App\Domain\Schools\Models\Organization;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<School> */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $address = fake()->optional()->address();

        return [
            'organization_id' => Organization::factory(),
            'name_ar' => fake()->company().' School',
            'name_en' => fake()->company().' School',
            'slug' => fake()->slug(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => $address,
            // Mirrored like the organisation above: a school that only has an
            // English address would show up as "missing Arabic" in the
            // translation sweep, which makes the backfill tests order-dependent.
            'address_ar' => $address,
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
