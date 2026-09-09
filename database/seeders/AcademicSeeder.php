<?php

namespace Database\Seeders;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->first();

        $academicYear = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name_en' => '2025-2026'],
            [
                'name_ar' => '٢٠٢٥-٢٠٢٦',
                'start_date' => '2025-09-01',
                'end_date' => '2026-06-30',
                'is_current' => true,
            ]
        );

        Semester::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name_en' => 'First Semester'],
            [
                'name_ar' => 'الفصل الدراسي الأول',
                'code' => 'S1',
                'start_date' => '2025-09-01',
                'end_date' => '2026-01-31',
                'is_current' => true,
            ]
        );

        Semester::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name_en' => 'Second Semester'],
            [
                'name_ar' => 'الفصل الدراسي الثاني',
                'code' => 'S2',
                'start_date' => '2026-02-01',
                'end_date' => '2026-06-30',
                'is_current' => false,
            ]
        );

        $gradeLevels = [
            ['name_ar' => 'الصف الأول', 'name_en' => 'Grade 1', 'code' => 'G1', 'level' => 1],
            ['name_ar' => 'الصف الثاني', 'name_en' => 'Grade 2', 'code' => 'G2', 'level' => 2],
            ['name_ar' => 'الصف الثالث', 'name_en' => 'Grade 3', 'code' => 'G3', 'level' => 3],
            ['name_ar' => 'الصف الرابع', 'name_en' => 'Grade 4', 'code' => 'G4', 'level' => 4],
            ['name_ar' => 'الصف الخامس', 'name_en' => 'Grade 5', 'code' => 'G5', 'level' => 5],
            ['name_ar' => 'الصف السادس', 'name_en' => 'Grade 6', 'code' => 'G6', 'level' => 6],
            ['name_ar' => 'الصف السابع', 'name_en' => 'Grade 7', 'code' => 'G7', 'level' => 7],
            ['name_ar' => 'الصف الثامن', 'name_en' => 'Grade 8', 'code' => 'G8', 'level' => 8],
            ['name_ar' => 'الصف التاسع', 'name_en' => 'Grade 9', 'code' => 'G9', 'level' => 9],
            ['name_ar' => 'الصف العاشر', 'name_en' => 'Grade 10', 'code' => 'G10', 'level' => 10],
            ['name_ar' => 'الصف الحادي عشر', 'name_en' => 'Grade 11', 'code' => 'G11', 'level' => 11],
            ['name_ar' => 'الصف الثاني عشر', 'name_en' => 'Grade 12', 'code' => 'G12', 'level' => 12],
        ];

        foreach ($gradeLevels as $gradeData) {
            GradeLevel::firstOrCreate(
                ['school_id' => $school->id, 'code' => $gradeData['code']],
                array_merge($gradeData, ['school_id' => $school->id])
            );
        }

        $subjects = [
            ['name_en' => 'Mathematics', 'name_ar' => 'الرياضيات', 'code' => 'MATH'],
            ['name_en' => 'Science', 'name_ar' => 'العلوم', 'code' => 'SCI'],
            ['name_en' => 'English', 'name_ar' => 'اللغة الإنجليزية', 'code' => 'ENG'],
            ['name_en' => 'Arabic', 'name_ar' => 'اللغة العربية', 'code' => 'ARB'],
            ['name_en' => 'Social Studies', 'name_ar' => 'الدراسات الاجتماعية', 'code' => 'SS'],
            ['name_en' => 'Physical Education', 'name_ar' => 'التربية البدنية', 'code' => 'PE'],
            ['name_en' => 'Art', 'name_ar' => 'الفنون', 'code' => 'ART'],
            ['name_en' => 'Computer Science', 'name_ar' => 'علوم الحاسب', 'code' => 'CS'],
        ];

        $gradeLevelRows = GradeLevel::where('school_id', $school->id)->orderBy('level')->get();

        foreach ($subjects as $index => $subjectData) {
            $gradeLevel = $gradeLevelRows[$index % $gradeLevelRows->count()] ?? null;

            Subject::updateOrCreate(
                ['school_id' => $school->id, 'code' => $subjectData['code']],
                array_merge($subjectData, [
                    'school_id' => $school->id,
                    'grade_level_id' => $gradeLevel?->id,
                ])
            );
        }
    }
}
