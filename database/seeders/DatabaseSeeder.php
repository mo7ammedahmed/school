<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder pipeline (order matters):
     *  1. Permissions & roles (Spatie)
     *  2. Organization & school (tenancy root)
     *  3. Users, memberships & portal accounts (Identity)
     *  4. Academic structure (years, semesters, grades, sections, subjects)
     *  5. People (guardians, teacher profiles, students, enrollments)
     *  6. Operations (offerings, timetable, attendance, assessment, learning, comms, docs, audit)
     *  7. Finance (fee types/structures, invoices, payments, refunds, gateway)
     *  8. Content & admissions (public site + admissions pipeline)
     *  9. Portal user links (students/guardians -> user accounts)
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            OrganizationSeeder::class,
            SchoolSeeder::class,
            UserSeeder::class,
            AcademicSeeder::class,
            PeopleSeeder::class,
            PortalUserSeeder::class,
            OperationsSeeder::class,
            FinanceSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
