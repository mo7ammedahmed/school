<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Grants each role its permissions.
 *
 * Super admin takes everything. Student and guardian are stated as allow lists,
 * so a permission added later is off their plate until someone names it.
 *
 * The five staff roles are stated as *deny* lists over the full permission
 * catalogue, which is the trap: a permission added to {@see PermissionSeeder}
 * lands in the hands of every staff role whose deny list does not name it. When
 * you add a permission, add its name to the deny list of each staff role that
 * must not hold it, in the same commit. `manage-discounts` and
 * `view-audit-logs` are the standing example.
 */
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
            'view-audit-logs',
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
            'manage-discounts',
            'view-audit-logs',
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
            'view-audit-logs',
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

        // ------------------------------------------------------------------
        // Derived grants: permissions the policies check but the allow/deny
        // lists above cannot infer.
        //
        // Adding a permission to PermissionSeeder hands it to every staff role
        // whose deny list does not name it, which is how accountant would end
        // up managing school settings. These are granted against the permission
        // each feature's route middleware already enforces, so the policy and
        // the route agree.
        // ------------------------------------------------------------------
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
            'manage-school-settings' => ['manage-settings'],
            'manage-classrooms' => ['manage-rooms'],
        ];

        foreach ($derivedPermissions as $permission => $parents) {
            $model = Permission::firstOrCreate(['name' => $permission]);
            $roles = Role::whereIn('name', [
                'super_admin', 'school_admin', 'principal', 'registrar',
                'teacher', 'accountant', 'student', 'guardian',
            ])->get()->filter(fn (Role $role) => collect($parents)->contains(
                fn (string $parent) => $role->checkPermissionTo($parent)
            ));
            $model->roles()->sync($roles);
        }
    }
}
