<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-dashboard',
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
            'manage-fee-types',
            'manage-fee-structures',
            'manage-fee-assignments',
            'manage-invoices',
            'manage-payments',
            'manage-refunds',
            'manage-payment-gateways',
            'manage-documents',
            'manage-announcements',
            'manage-messages',
            'manage-reports',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
