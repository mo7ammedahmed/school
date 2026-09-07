<?php

namespace Database\Seeders;

use App\Domain\Schools\Models\School;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->first();

        $academicYear = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2025-2026'],
            [
                'start_date' => '2025-09-01',
                'end_date' => '2026-06-30',
                'is_current' => true,
            ]
        );

        Semester::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'First Semester'],
            [
                'code' => 'S1',
                'start_date' => '2025-09-01',
                'end_date' => '2026-01-31',
                'is_current' => true,
            ]
        );

        Semester::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Second Semester'],
            [
                'code' => 'S2',
                'start_date' => '2026-02-01',
                'end_date' => '2026-06-30',
                'is_current' => false,
            ]
        );

        $gradeLevels = [
            ['name' => 'Grade 1', 'code' => 'G1', 'level' => 1],
            ['name' => 'Grade 2', 'code' => 'G2', 'level' => 2],
            ['name' => 'Grade 3', 'code' => 'G3', 'level' => 3],
            ['name' => 'Grade 4', 'code' => 'G4', 'level' => 4],
            ['name' => 'Grade 5', 'code' => 'G5', 'level' => 5],
            ['name' => 'Grade 6', 'code' => 'G6', 'level' => 6],
            ['name' => 'Grade 7', 'code' => 'G7', 'level' => 7],
            ['name' => 'Grade 8', 'code' => 'G8', 'level' => 8],
            ['name' => 'Grade 9', 'code' => 'G9', 'level' => 9],
            ['name' => 'Grade 10', 'code' => 'G10', 'level' => 10],
            ['name' => 'Grade 11', 'code' => 'G11', 'level' => 11],
            ['name' => 'Grade 12', 'code' => 'G12', 'level' => 12],
        ];

        foreach ($gradeLevels as $gradeData) {
            GradeLevel::firstOrCreate(
                ['school_id' => $school->id, 'code' => $gradeData['code']],
                array_merge($gradeData, ['school_id' => $school->id])
            );
        }

        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Arabic', 'code' => 'ARB'],
            ['name' => 'Social Studies', 'code' => 'SS'],
            ['name' => 'Physical Education', 'code' => 'PE'],
            ['name' => 'Art', 'code' => 'ART'],
            ['name' => 'Computer Science', 'code' => 'CS'],
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate(
                ['school_id' => $school->id, 'code' => $subjectData['code']],
                array_merge($subjectData, ['school_id' => $school->id])
            );
        }
    }
}
