<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Schools\Models\School;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adding a grade level, section, semester or subject used to fail (or silently
 * drop fields) because the school and the required foreign keys were never set.
 */
class EntityCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_grade_level_is_created_for_the_active_school(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->post('/grade-levels', [
            'name_en' => 'Grade One',
            'name_ar' => 'الصف الأول',
            'level' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('grade_levels', [
            'school_id' => $school->id,
            'name_en' => 'Grade One',
            'level' => 1,
        ]);
    }

    public function test_a_section_is_created_with_its_academic_year_and_grade_level(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        [$year, $gradeLevel] = $this->yearAndGradeLevel($school);

        $this->post('/sections', [
            'name_en' => 'Section A',
            'name_ar' => 'شعبة أ',
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $year->id,
            'capacity' => 30,
        ])->assertRedirect();

        $this->assertDatabaseHas('sections', [
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $gradeLevel->id,
            'name_en' => 'Section A',
        ]);
    }

    public function test_a_semester_is_created_with_its_academic_year(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        [$year] = $this->yearAndGradeLevel($school);

        $this->post('/semesters', [
            'name_en' => 'First Semester',
            'name_ar' => 'الفصل الأول',
            'academic_year_id' => $year->id,
            'code' => 'S1',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
            'is_current' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('semesters', [
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'code' => 'S1',
            'is_current' => true,
        ]);
    }

    public function test_a_semester_requires_an_academic_year(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->post('/semesters', [
            'name_en' => 'First Semester',
            'code' => 'S1',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
        ])->assertSessionHasErrors('academic_year_id');
    }

    public function test_a_subject_is_created_with_its_grade_level(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        [, $gradeLevel] = $this->yearAndGradeLevel($school);

        $this->post('/subjects', [
            'name_en' => 'Mathematics',
            'name_ar' => 'الرياضيات',
            'code' => 'MATH',
            'grade_level_id' => $gradeLevel->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'grade_level_id' => $gradeLevel->id,
            'code' => 'MATH',
        ]);
    }

    public function test_another_schools_grade_level_cannot_be_reused(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $foreignGradeLevel = GradeLevel::factory()->create(['school_id' => $other->id]);

        $this->post('/subjects', [
            'name_en' => 'Mathematics',
            'code' => 'MATH',
            'grade_level_id' => $foreignGradeLevel->id,
        ])->assertSessionHasErrors('grade_level_id');
    }

    public function test_another_schools_record_is_not_editable(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $foreignSection = Section::factory()->create([
            'school_id' => $other->id,
            'academic_year_id' => AcademicYear::factory()->create(['school_id' => $other->id])->id,
            'grade_level_id' => GradeLevel::factory()->create(['school_id' => $other->id])->id,
        ]);

        $this->get("/sections/{$foreignSection->id}/edit")->assertForbidden();
        $this->get("/sections/{$foreignSection->id}")->assertForbidden();
    }

    public function test_a_section_code_is_unique_per_school_not_globally(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        // A subject in another school with the same code.
        Subject::create([
            'school_id' => $other->id,
            'name_en' => 'Mathematics',
            'code' => 'MATH',
        ]);

        $this->actingAsSchoolUser($school);

        [, $ownGradeLevel] = $this->yearAndGradeLevel($school);

        // The same code in a different school must be accepted.
        $this->post('/subjects', [
            'name_en' => 'Mathematics',
            'code' => 'MATH',
            'grade_level_id' => $ownGradeLevel->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, Subject::where('code', 'MATH')->count());
    }

    /**
     * @return array{0: AcademicYear, 1: GradeLevel}
     */
    private function yearAndGradeLevel(School $school): array
    {
        return [
            AcademicYear::factory()->create(['school_id' => $school->id]),
            GradeLevel::factory()->create(['school_id' => $school->id]),
        ];
    }
}
