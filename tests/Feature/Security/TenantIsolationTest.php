<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Enrollment;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Finance\Models\Discount;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One school must not be able to reach another school's rows by guessing an id.
 *
 * Three screens were written without any tenant filter at all:
 *
 *   GET  /enrollments                 listed every school's enrolments
 *   GET  /enrollments/{enrollment}    opened one by id, whoever owned it
 *   PUT  /enrollments/{enrollment}    edited it
 *   DELETE /enrollments/{enrollment}  deleted it
 *
 *   GET  /admissions/review/{application}              read a foreign applicant's PII
 *   POST /admissions/review/{application}/assign       assigned it to a reviewer
 *   POST /admissions/review/{application}/priority     re-prioritised it
 *   POST /admissions/review/{application}/notes        annotated it
 *
 *   GET  /finance/discounts           listed every school's discounts
 *   GET  /finance/discounts/{discount} etc.
 *
 * Every one of these is reached through implicit route-model binding, which
 * resolves the id with no `school_id` in the query. The table below asserts the
 * behaviour the application is required to have: the list shows only the active
 * school, and a foreign id answers 404 — not 403. A 403 confirms the row exists
 * and belongs to somebody else, which is itself a way to enumerate other
 * tenants; a tenant-scoped binding simply never finds it.
 *
 * This is the discovery half of Phase 4. The fixes land one controller at a
 * time, so each assertion below starts red and turns green as its screen is
 * scoped.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_enrolment_list_shows_only_the_active_schools_rows(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $mine = $this->enrollmentFor($school);
        $this->enrollmentFor($other);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $response = $this->get('/enrollments');

        $response->assertOk();

        $rows = $response->viewData('page')['props']['enrollments']['data'];

        $this->assertSame([$mine->id], array_column($rows, 'id'), "another school's enrolments leaked into the list");
    }

    public function test_opening_another_schools_enrolment_is_refused(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->enrollmentFor($other);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $this->get("/enrollments/{$foreign->id}")->assertNotFound();
        $this->get("/enrollments/{$foreign->id}/edit")->assertNotFound();
    }

    public function test_updating_another_schools_enrolment_is_refused(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->enrollmentFor($other);
        [$student, $year, $section] = $this->academicsFor($school);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $this->put("/enrollments/{$foreign->id}", [
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => '2026-09-15',
            'status' => 'withdrawn',
        ])->assertNotFound();

        $this->assertSame('active', $foreign->refresh()->status, "another school's enrolment was edited");
    }

    public function test_deleting_another_schools_enrolment_is_refused(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->enrollmentFor($other);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $this->delete("/enrollments/{$foreign->id}")->assertNotFound();

        $this->assertDatabaseHas('enrollments', ['id' => $foreign->id, 'deleted_at' => null]);
    }

    public function test_enrolling_another_schools_student_is_a_validation_error(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreignStudent = Student::factory()->create(['school_id' => $other->id]);
        [, $year, $section] = $this->academicsFor($school);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $this->post('/enrollments', [
            'student_id' => $foreignStudent->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => '2026-09-01',
            'status' => 'active',
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('enrollments', ['student_id' => $foreignStudent->id]);
    }

    public function test_enrolling_into_another_schools_section_is_a_validation_error(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        [$student, $year] = $this->academicsFor($school);
        [, , $foreignSection] = $this->academicsFor($other);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $this->post('/enrollments', [
            'student_id' => $student->id,
            'section_id' => $foreignSection->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => '2026-09-01',
            'status' => 'active',
        ])->assertSessionHasErrors('section_id');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_the_enrolment_form_lists_only_the_active_schools_students_and_sections(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $mine = Student::factory()->create(['school_id' => $school->id]);
        Student::factory()->create(['school_id' => $other->id]);
        [, , $section] = $this->academicsFor($school);
        [, , $foreignSection] = $this->academicsFor($other);

        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $response = $this->get('/enrollments/create');

        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $studentIds = array_column($props['students'], 'id');
        $sectionIds = array_column($props['sections'], 'id');

        // `academicsFor()` creates a student and a section per school, so the
        // comparison is against the school's own rows rather than one fixture.
        $this->assertContains($mine->id, $studentIds);
        $this->assertEqualsCanonicalizing(
            $school->students()->pluck('id')->all(),
            $studentIds,
            "another school's students leaked into the enrolment form",
        );
        $this->assertContains($section->id, $sectionIds);
        $this->assertEqualsCanonicalizing(
            $school->sections()->pluck('id')->all(),
            $sectionIds,
        );
        $this->assertNotContains($foreignSection->id, $sectionIds);
    }

    public function test_another_schools_application_cannot_be_opened_for_review(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->applicationFor($other);

        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $this->get("/admissions/review/{$foreign->id}")->assertNotFound();
    }

    public function test_another_schools_application_cannot_be_assigned(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->applicationFor($other);
        $reviewer = $this->actingAsSchoolUser($school, ['manage-admissions']);

        $this->post("/admissions/review/{$foreign->id}/assign", [
            'reviewer_id' => $reviewer->id,
        ])->assertNotFound();

        $this->assertNull($foreign->refresh()->assigned_to, "another school's application was assigned");
    }

    public function test_another_schools_application_cannot_be_prioritised(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->applicationFor($other);

        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $this->post("/admissions/review/{$foreign->id}/priority", [
            'priority' => 'urgent',
        ])->assertNotFound();

        $this->assertSame('medium', $foreign->refresh()->priority, "another school's application was re-prioritised");
    }

    public function test_another_schools_application_cannot_be_annotated(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->applicationFor($other);

        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $this->post("/admissions/review/{$foreign->id}/notes", [
            'notes' => 'Should never be written.',
        ])->assertNotFound();

        $this->assertNull($foreign->refresh()->internal_notes, "another school's application was annotated");
    }

    public function test_the_discount_list_shows_only_the_active_schools_rows(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $mine = $this->discountFor($school);
        $this->discountFor($other);

        $this->actingAsSchoolUser($school, ['manage-discounts']);

        $response = $this->get('/finance/discounts');

        $response->assertOk();

        $rows = $response->viewData('page')['props']['discounts']['data'];

        $this->assertSame([$mine->id], array_column($rows, 'id'), "another school's discounts leaked into the list");
    }

    public function test_another_schools_discount_cannot_be_opened_or_edited(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->discountFor($other);

        $this->actingAsSchoolUser($school, ['manage-discounts']);

        $this->get("/finance/discounts/{$foreign->id}")->assertNotFound();
        $this->get("/finance/discounts/{$foreign->id}/edit")->assertNotFound();

        $this->put("/finance/discounts/{$foreign->id}", [
            'name' => 'Hijacked',
            'type' => 'percentage',
            'value' => 99,
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_active' => 1,
        ])->assertNotFound();

        $foreign->refresh();

        $this->assertSame('Sibling discount', $foreign->name, "another school's discount was edited");
        $this->assertSame('10.0000', (string) $foreign->value);
    }

    public function test_another_schools_discount_cannot_be_deleted(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->discountFor($other);

        $this->actingAsSchoolUser($school, ['manage-discounts']);

        $this->delete("/finance/discounts/{$foreign->id}")->assertNotFound();

        $this->assertDatabaseHas('discounts', ['id' => $foreign->id, 'deleted_at' => null]);
    }

    private function enrollmentFor(School $school): Enrollment
    {
        [$student, $year, $section] = $this->academicsFor($school);

        return Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: Student, 1: AcademicYear, 2: Section}
     */
    private function academicsFor(School $school): array
    {
        $student = Student::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $school->id]);

        $section = Section::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        return [$student, $year, $section];
    }

    private function applicationFor(School $school): AdmissionApplication
    {
        return AdmissionApplication::create([
            'school_id' => $school->id,
            'reference' => 'APP-'.fake()->unique()->numberBetween(100000, 999999),
            'status' => 'submitted',
            'priority' => 'medium',
            'guardian_first_name' => 'Amal',
            'guardian_last_name' => 'Rahman',
            'guardian_email' => 'amal@example.test',
            'student_first_name' => 'Nour',
            'student_last_name' => 'Rahman',
            'submitted_at' => now(),
        ]);
    }

    private function discountFor(School $school): Discount
    {
        return Discount::create([
            'school_id' => $school->id,
            'name' => 'Sibling discount',
            'type' => 'percentage',
            'value' => 10,
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
    }
}
