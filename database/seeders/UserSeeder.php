<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@aether.school'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $superAdmin->assignRole('super_admin');

        $schoolAdmin = User::firstOrCreate(
            ['email' => 'admin@alnoor.school'],
            [
                'name' => 'School Admin',
                'password' => bcrypt('password'),
            ]
        );
        $schoolAdmin->assignRole('school_admin');

        $principal = User::firstOrCreate(
            ['email' => 'principal@alnoor.school'],
            [
                'name' => 'Principal',
                'password' => bcrypt('password'),
            ]
        );
        $principal->assignRole('principal');

        $registrar = User::firstOrCreate(
            ['email' => 'registrar@alnoor.school'],
            [
                'name' => 'Registrar',
                'password' => bcrypt('password'),
            ]
        );
        $registrar->assignRole('registrar');

        $teacher = User::firstOrCreate(
            ['email' => 'teacher@alnoor.school'],
            [
                'name' => 'Teacher',
                'password' => bcrypt('password'),
            ]
        );
        $teacher->assignRole('teacher');

        $accountant = User::firstOrCreate(
            ['email' => 'accountant@alnoor.school'],
            [
                'name' => 'Accountant',
                'password' => bcrypt('password'),
            ]
        );
        $accountant->assignRole('accountant');
    }
}
