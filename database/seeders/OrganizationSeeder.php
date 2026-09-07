<?php

namespace Database\Seeders;

use App\Domain\Schools\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['slug' => 'al-noor-organization'],
            [
                'name' => 'Al Noor Organization',
                'email' => 'info@alnoor.school',
                'phone' => '+966501234567',
                'address' => '123 Education Street, Riyadh, Saudi Arabia',
            ]
        );
    }
}
