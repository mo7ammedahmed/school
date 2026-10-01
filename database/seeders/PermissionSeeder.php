<?php

declare(strict_types=1);

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
            'manage-rooms',
            'manage-periods',
            'manage-calendar',
            'manage-timetable-entries',
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
            'manage-discounts',
            'manage-documents',
            'manage-announcements',
            'manage-messages',
            'manage-reports',
            'manage-settings',
            'manage-content',
            'manage-admissions',
            'view-audit-logs',
            'view-own-children',
            'view-own-grades',
            'view-own-attendance',
            'view-own-fees',
            'view-own-schedule',
            'submit-assignments',
            'take-quizzes',

            // Permissions checked by policies but not previously seeded.
            // Added in the same order as their parent permissions above.
            'manage-grading-scales',
            'manage-grading-categories',
            'manage-assessment-scores',
            'manage-exam-results',
            'manage-attendance-records',
            'manage-attendance-sessions',
            'manage-conversations',
            'manage-notifications',
            'manage-news',
            'manage-events',
            'manage-faqs',
            'manage-staff-profiles',
            'manage-contact-leads',
            'manage-document-categories',
            'manage-gateway-transactions',
            'manage-webhook-events',
            'manage-invoice-lines',
            'manage-payment-allocations',
            'manage-memberships',
            'manage-quiz-attempts',
            'manage-classrooms',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
