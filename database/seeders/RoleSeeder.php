<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $schoolAdmin = Role::firstOrCreate(['name' => 'school_admin']);
        $principal = Role::firstOrCreate(['name' => 'principal']);
        $registrar = Role::firstOrCreate(['name' => 'registrar']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $student = Role::firstOrCreate(['name' => 'student']);
        $guardian = Role::firstOrCreate(['name' => 'guardian']);

        $allPermissions = Permission::all();

        $superAdmin->syncPermissions($allPermissions);

        $schoolAdminPermissions = $allPermissions->filter(fn ($p) => ! in_array($p->name, [
            'manage-schools',
        ]));
        $schoolAdmin->syncPermissions($schoolAdminPermissions);

        $principalPermissions = $allPermissions->filter(fn ($p) => ! in_array($p->name, [
            'manage-schools',
            'manage-users',
            'manage-roles',
            'manage-fee-types',
            'manage-fee-structures',
            'manage-fee-assignments',
            'manage-invoices',
            'manage-payments',
            'manage-refunds',
            'manage-payment-gateways',
        ]));
        $principal->syncPermissions($principalPermissions);

        $registrarPermissions = $allPermissions->filter(fn ($p) => ! in_array($p->name, [
            'manage-schools',
            'manage-users',
            'manage-roles',
            'manage-fee-types',
            'manage-fee-structures',
            'manage-invoices',
            'manage-payments',
            'manage-refunds',
            'manage-payment-gateways',
            'manage-settings',
            'manage-content',
        ]));
        $registrar->syncPermissions($registrarPermissions);

        $teacherPermissions = $allPermissions->filter(fn ($p) => ! in_array($p->name, [
            'manage-schools',
            'manage-users',
            'manage-roles',
            'manage-fee-types',
            'manage-fee-structures',
            'manage-fee-assignments',
            'manage-invoices',
            'manage-payments',
            'manage-refunds',
            'manage-payment-gateways',
            'manage-settings',
            'manage-content',
            'manage-admissions',
            'manage-students',
            'manage-teachers',
            'manage-guardians',
            'manage-academic-years',
            'manage-semesters',
            'manage-grade-levels',
            'manage-sections',
            'manage-subjects',
            'manage-offerings',
            'manage-enrollments',
        ]));
        $teacher->syncPermissions($teacherPermissions);

        $accountantPermissions = $allPermissions->filter(fn ($p) => ! in_array($p->name, [
            'manage-schools',
            'manage-users',
            'manage-roles',
            'manage-students',
            'manage-teachers',
            'manage-guardians',
            'manage-academic-years',
            'manage-semesters',
            'manage-grade-levels',
            'manage-sections',
            'manage-subjects',
            'manage-offerings',
            'manage-enrollments',
            'manage-attendance',
            'manage-assessments',
            'manage-exams',
            'manage-report-cards',
            'manage-materials',
            'manage-assignments',
            'manage-quizzes',
            'manage-submissions',
            'manage-settings',
            'manage-content',
            'manage-admissions',
            'view-own-children',
            'view-own-grades',
            'view-own-attendance',
            'view-own-fees',
            'view-own-schedule',
            'submit-assignments',
            'take-quizzes',
        ]));
        $accountant->syncPermissions($accountantPermissions);

        $studentPermissions = $allPermissions->filter(fn ($p) => in_array($p->name, [
            'view-dashboard',
            'view-own-grades',
            'view-own-attendance',
            'view-own-fees',
            'view-own-schedule',
            'submit-assignments',
            'take-quizzes',
        ]));
        $student->syncPermissions($studentPermissions);

        $guardianPermissions = $allPermissions->filter(fn ($p) => in_array($p->name, [
            'view-dashboard',
            'view-own-children',
            'view-own-grades',
            'view-own-attendance',
            'view-own-fees',
            'view-own-schedule',
        ]));
        $guardian->syncPermissions($guardianPermissions);
    }
}
