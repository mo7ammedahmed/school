<?php

namespace Database\Seeders;

use App\Domain\Schools\Models\Organization;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'al-noor-organization')->first();

        School::firstOrCreate(
            ['slug' => 'al-noor-school'],
            [
                'organization_id' => $organization->id,
                'name' => 'Al Noor School',
                'email' => 'admissions@alnoor.school',
                'phone' => '+966501234567',
                'address' => '123 Education Street, Riyadh, Saudi Arabia',
                'city' => 'Riyadh',
                'country' => 'SA',
                'timezone' => 'Asia/Riyadh',
                'locale' => 'en',
                'currency' => 'SAR',
                'primary_color' => '#065f46',
                'secondary_color' => '#d97706',
            ]
        );
    }
}
