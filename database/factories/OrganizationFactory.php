<?php

namespace Database\Factories;

use App\Domain\Schools\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();
        $address = fake()->optional()->address();

        return [
            'name' => $name,
            // Organisations are bilingual like schools, so a fresh tenant never
            // shows up as "missing Arabic" on the translations screen.
            'name_ar' => 'مؤسسة '.$name,
            'slug' => fake()->slug(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => $address,
            'address_ar' => $address,
            'logo_path' => null,
            'metadata' => null,
        ];
    }
}
