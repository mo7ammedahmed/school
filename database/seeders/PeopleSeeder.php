<?php

namespace Database\Seeders;

use App\Domain\Schools\Models\School;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PeopleSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->first();
        $academicYear = AcademicYear::where('school_id', $school->id)->where('is_current', true)->first();

        $guardian1 = Guardian::firstOrCreate(
            ['school_id' => $school->id, 'email' => 'guardian1@example.com'],
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Al-Rashid',
                'relationship' => 'Father',
                'phone' => '+966501111111',
                'address' => '123 Education Street, Riyadh',
                'occupation' => 'Engineer',
            ]
        );

        $guardian2 = Guardian::firstOrCreate(
            ['school_id' => $school->id, 'email' => 'guardian2@example.com'],
            [
                'first_name' => 'Fatima',
                'last_name' => 'Al-Rashid',
                'relationship' => 'Mother',
                'phone' => '+966501111112',
                'address' => '123 Education Street, Riyadh',
                'occupation' => 'Doctor',
            ]
        );

        $teacherProfile = TeacherProfile::firstOrCreate(
            ['school_id' => $school->id, 'email' => 'teacher@alnoor.school'],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'employee_id' => 'T001',
                'phone' => '+966502222222',
                'hire_date' => '2020-09-01',
                'qualification' => 'M.Ed',
                'specialization' => 'Mathematics',
                'bio' => 'Experienced mathematics teacher with 10 years of teaching experience.',
            ]
        );

        // Additional faculty so offerings/timetable/attendance are realistic
        $extraTeachers = [
            ['Mariam', 'Al-Sayed', 'T002', 'PE', 'mariam.alsayed@alnoor.school'],
            ['Omar', 'Haddad', 'T003', 'Arabic', 'omar.haddad@alnoor.school'],
            ['Elena', 'Petrova', 'T004', 'Science', 'elena.petrova@alnoor.school'],
            ['David', 'Okonkwo', 'T005', 'English', 'david.okonkwo@alnoor.school'],
            ['Aisha', 'Rahman', 'T006', 'Computer Science', 'aisha.rahman@alnoor.school'],
            ['Yusuf', 'Karim', 'T007', 'Social Studies', 'yusuf.karim@alnoor.school'],
        ];

        foreach ($extraTeachers as $i => [$first, $last, $employeeId, $specialization, $email]) {
            TeacherProfile::firstOrCreate(
                ['school_id' => $school->id, 'email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'employee_id' => $employeeId,
                    'phone' => '+9665022232' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'hire_date' => '2021-08-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'qualification' => 'B.Ed',
                    'specialization' => $specialization,
                    'bio' => "$first $last teaches $specialization at Al Noor School.",
                ]
            );
        }

        $gradeLevels = GradeLevel::where('school_id', $school->id)->get();

        foreach ($gradeLevels as $gradeLevel) {
            $section = Section::firstOrCreate(
                ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'grade_level_id' => $gradeLevel->id, 'code' => 'A'],
                [
                    'name' => 'Section A',
                    'capacity' => 30,
                    'current_count' => 0,
                ]
            );

            for ($i = 1; $i <= 5; $i++) {
                $student = Student::create([
                    'school_id' => $school->id,
                    'first_name' => 'Student',
                    'last_name' => $gradeLevel->name . ' ' . $i,
                    'student_id_number' => strtoupper(Str::random(8)),
                    'date_of_birth' => now()->subYears(10 + $gradeLevel->level)->format('Y-m-d'),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'nationality' => 'Saudi',
                    'address' => '123 Education Street, Riyadh',
                    'phone' => '+966500000000' . $i,
                    'enrollment_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]);

                GuardianRelationship::firstOrCreate(
                    ['school_id' => $school->id, 'guardian_id' => $guardian1->id, 'student_id' => $student->id],
                    ['is_primary' => true, 'is_financial_guardian' => true]
                );

                GuardianRelationship::firstOrCreate(
                    ['school_id' => $school->id, 'guardian_id' => $guardian2->id, 'student_id' => $student->id],
                    ['is_primary' => false, 'is_financial_guardian' => false]
                );

                Enrollment::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'section_id' => $section->id,
                    'enrollment_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]);
            }
        }
    }
}
