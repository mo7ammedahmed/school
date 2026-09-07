<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Identity\Models\UserMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates portal-linked users (student/guardian accounts) and memberships
 * for every staff role, so all personas can be demonstrated end-to-end.
 */
class PortalUserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->firstOrFail();

        // --- Staff memberships (school context for each role) ---
        $staff = [
            'superadmin@aether.school' => 'super_admin',
            'admin@alnoor.school' => 'school_admin',
            'principal@alnoor.school' => 'principal',
            'registrar@alnoor.school' => 'registrar',
            'teacher@alnoor.school' => 'teacher',
            'accountant@alnoor.school' => 'accountant',
        ];

        foreach ($staff as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                UserMembership::firstOrCreate(
                    ['user_id' => $user->id, 'school_id' => $school->id],
                    ['role' => $role, 'is_active' => true]
                );
            }
        }

        // --- Teacher user accounts (linked to teacher profiles) ---
        $teachers = TeacherProfile::where('school_id', $school->id)->get();
        foreach ($teachers as $index => $teacher) {
            $email = $teacher->email ?? strtolower($teacher->first_name.'.'.$teacher->last_name).'@alnoor.school';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $teacher->first_name.' '.$teacher->last_name,
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('teacher');

            $teacher->update(['user_id' => $user->id]);

            UserMembership::firstOrCreate(
                ['user_id' => $user->id, 'school_id' => $school->id],
                ['role' => 'teacher', 'is_active' => true]
            );
        }

        // --- Guardian user account(s) linked to guardian records ---
        $guardians = Guardian::where('school_id', $school->id)->get();
        foreach ($guardians as $index => $guardian) {
            $email = $guardian->email ?? 'guardian'.$index.'@example.com';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $guardian->first_name.' '.$guardian->last_name,
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('guardian');

            $guardian->update(['user_id' => $user->id]);

            UserMembership::firstOrCreate(
                ['user_id' => $user->id, 'school_id' => $school->id],
                ['role' => 'guardian', 'is_active' => true]
            );
        }

        // --- Student user account for one student (student portal demo) ---
        $student = Student::where('school_id', $school->id)->first();
        if ($student) {
            $user = User::firstOrCreate(
                ['email' => 'student@alnoor.school'],
                [
                    'name' => $student->first_name.' '.$student->last_name,
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('student');

            $student->update(['user_id' => $user->id]);

            UserMembership::firstOrCreate(
                ['user_id' => $user->id, 'school_id' => $school->id],
                ['role' => 'student', 'is_active' => true]
            );
        }
    }
}
