<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Services\SharedPermissionList;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** New permissions require an explicit grant; only platform administrators inherit them. */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($allPermissions);

        $grants = [
            'school_admin' => [
                'view-dashboard', 'manage-users', 'manage-roles',
                'manage-students', 'manage-teachers', 'manage-guardians',
                'manage-academic-years', 'manage-semesters', 'manage-grade-levels',
                'manage-sections', 'manage-rooms', 'manage-periods',
                'manage-calendar', 'manage-timetable-entries', 'manage-subjects',
                'manage-offerings', 'manage-enrollments', 'manage-attendance',
                'manage-assessments', 'manage-exams', 'manage-report-cards',
                'manage-materials', 'manage-assignments', 'manage-quizzes',
                'manage-submissions', 'manage-fee-types', 'manage-fee-structures',
                'manage-fee-assignments', 'manage-invoices', 'manage-payments',
                'manage-refunds', 'manage-payment-gateways', 'manage-discounts',
                'manage-documents', 'manage-announcements', 'manage-messages',
                'manage-reports', 'manage-settings', 'manage-content',
                'manage-admissions', 'view-audit-logs', 'view-own-children',
                'view-own-grades', 'view-own-attendance', 'view-own-fees',
                'view-own-schedule', 'submit-assignments', 'take-quizzes',
                'manage-live-sessions', 'view-own-lessons',
            ],
            'principal' => [
                'view-dashboard', 'manage-students', 'manage-teachers',
                'manage-guardians', 'manage-academic-years', 'manage-semesters',
                'manage-grade-levels', 'manage-sections', 'manage-rooms',
                'manage-periods', 'manage-calendar', 'manage-timetable-entries',
                'manage-subjects', 'manage-offerings', 'manage-enrollments',
                'manage-attendance', 'manage-assessments', 'manage-exams',
                'manage-report-cards', 'manage-materials', 'manage-assignments',
                'manage-quizzes', 'manage-submissions', 'manage-discounts',
                'manage-documents', 'manage-announcements', 'manage-messages',
                'manage-reports', 'manage-settings', 'manage-content',
                'manage-admissions', 'view-audit-logs', 'view-own-children',
                'view-own-grades', 'view-own-attendance', 'view-own-fees',
                'view-own-schedule', 'submit-assignments', 'take-quizzes',
                'manage-live-sessions', 'view-own-lessons',
            ],
            'registrar' => [
                'view-dashboard', 'manage-students', 'manage-teachers',
                'manage-guardians', 'manage-academic-years', 'manage-semesters',
                'manage-grade-levels', 'manage-sections', 'manage-rooms',
                'manage-periods', 'manage-calendar', 'manage-timetable-entries',
                'manage-subjects', 'manage-offerings', 'manage-enrollments',
                'manage-attendance', 'manage-assessments', 'manage-exams',
                'manage-report-cards', 'manage-materials', 'manage-assignments',
                'manage-quizzes', 'manage-submissions', 'manage-fee-assignments',
                'manage-discounts', 'manage-documents', 'manage-announcements',
                'manage-messages', 'manage-reports', 'manage-admissions',
                'view-own-children', 'view-own-grades', 'view-own-attendance',
                'view-own-fees', 'view-own-schedule', 'submit-assignments',
                'take-quizzes', 'view-own-lessons',
            ],
            'teacher' => [
                'view-dashboard', 'manage-rooms', 'manage-periods',
                'manage-calendar', 'manage-timetable-entries', 'manage-attendance',
                'manage-assessments', 'manage-exams', 'manage-report-cards',
                'manage-materials', 'manage-assignments', 'manage-quizzes',
                'manage-submissions', 'manage-documents', 'manage-announcements',
                'manage-messages', 'manage-reports', 'view-own-children',
                'view-own-grades', 'view-own-attendance', 'view-own-fees',
                'view-own-schedule', 'submit-assignments', 'take-quizzes',
                'manage-live-sessions', 'view-own-lessons',
            ],
            'accountant' => [
                'view-dashboard', 'manage-rooms', 'manage-periods',
                'manage-calendar', 'manage-timetable-entries', 'manage-fee-types',
                'manage-fee-structures', 'manage-fee-assignments', 'manage-invoices',
                'manage-payments', 'manage-refunds', 'manage-payment-gateways',
                'manage-discounts', 'manage-documents', 'manage-announcements',
                'manage-messages', 'manage-reports', 'view-own-lessons',
            ],
            'student' => [
                'view-dashboard', 'view-own-grades', 'view-own-attendance',
                'view-own-fees', 'view-own-schedule', 'submit-assignments',
                'take-quizzes', 'view-own-lessons',
            ],
            'guardian' => [
                'view-dashboard', 'view-own-children', 'view-own-grades',
                'view-own-attendance', 'view-own-fees', 'view-own-schedule',
            ],
        ];

        foreach ($grants as $name => $permissions) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
                ->syncPermissions($allPermissions->whereIn('name', $permissions));
        }

        // Child policies inherit the route's parent permission.
        $derivedPermissions = [
            'manage-grading-scales' => ['manage-settings'],
            'manage-grading-categories' => ['manage-settings'],
            'manage-assessment-scores' => ['manage-assessments'],
            'manage-exam-results' => ['manage-exams'],
            'manage-attendance-records' => ['manage-attendance'],
            'manage-attendance-sessions' => ['manage-attendance'],
            'manage-conversations' => ['manage-messages'],
            'manage-notifications' => ['manage-messages'],
            'manage-news' => ['manage-content'],
            'manage-events' => ['manage-content'],
            'manage-faqs' => ['manage-content'],
            'manage-staff-profiles' => ['manage-content'],
            'manage-contact-leads' => ['manage-content'],
            'manage-document-categories' => ['manage-documents'],
            'manage-gateway-transactions' => ['manage-settings'],
            'manage-webhook-events' => ['manage-settings'],
            'manage-invoice-lines' => ['manage-invoices'],
            'manage-payment-allocations' => ['manage-payments'],
            'manage-memberships' => ['manage-users'],
            'manage-quiz-attempts' => ['manage-quizzes'],
            'manage-classrooms' => ['manage-rooms'],
        ];

        foreach ($derivedPermissions as $permission => $parents) {
            $model = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $roles = Role::where('guard_name', 'web')->whereIn('name', [
                'super_admin', 'school_admin', 'principal', 'registrar',
                'teacher', 'accountant', 'student', 'guardian',
            ])->get()->filter(fn (Role $role) => collect($parents)->contains(
                fn (string $parent) => $role->checkPermissionTo($parent)
            ));
            $model->roles()->sync($roles);
        }

        app(SharedPermissionList::class)->flush();
    }
}
