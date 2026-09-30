<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Compliance\Models\AuditLog;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Approving, rejecting and converting an application.
 *
 * None of this was covered, which is how the decision endpoint kept a query that
 * gave the recorded id to the wrong branch: `and` binds tighter than `or`, so
 * `where(school)->where(status, 'submitted')->orWhere(id + other status)` matched
 * *any* submitted application in the school, and the decision landed on whichever
 * one came back first.
 */
class ApplicationDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_an_application_records_the_decision_and_its_event(): void
    {
        $school = School::factory()->create();
        $reviewer = $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school);

        $this->post("/admissions/applications/{$application->id}/decide", [
            'decision' => 'approved',
            'notes' => 'Strong candidate',
        ])->assertRedirect();

        $application->refresh();

        $this->assertSame('approved', $application->status);
        $this->assertSame($reviewer->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
        $this->assertSame('Strong candidate', $application->review_notes);

        $this->assertDatabaseHas('admission_application_events', [
            'admission_application_id' => $application->id,
            'event_type' => 'approved',
        ]);
    }

    public function test_rejecting_an_application_keeps_the_reason(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school);

        $this->post("/admissions/applications/{$application->id}/decide", [
            'decision' => 'rejected',
            'notes' => 'No place in this grade',
        ])->assertRedirect();

        $application->refresh();

        $this->assertSame('rejected', $application->status);
        $this->assertSame('No place in this grade', $application->review_notes);
    }

    public function test_deciding_on_one_application_leaves_another_submitted_one_alone(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        // The older application is the one the broken query used to pick up.
        $older = $this->application($school, ['submitted_at' => now()->subDay()]);
        $target = $this->application($school);

        $this->post("/admissions/applications/{$target->id}/decide", ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $target->refresh()->status);
        $this->assertSame('submitted', $older->refresh()->status);
        $this->assertNull($older->reviewed_at);
    }

    public function test_an_under_review_application_can_still_be_decided(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school, ['status' => 'under_review']);

        $this->post("/admissions/applications/{$application->id}/decide", ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $application->refresh()->status);
    }

    public function test_another_schools_application_cannot_be_decided(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($other);

        $this->post("/admissions/applications/{$application->id}/decide", ['decision' => 'approved'])
            ->assertNotFound();

        $this->assertSame('submitted', $application->refresh()->status);
    }

    public function test_an_already_decided_application_cannot_be_decided_again(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school, ['status' => 'rejected']);

        $this->post("/admissions/applications/{$application->id}/decide", ['decision' => 'approved'])
            ->assertNotFound();

        $this->assertSame('rejected', $application->refresh()->status);
    }

    public function test_the_decision_must_be_an_approval_or_a_rejection(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school);

        $this->post("/admissions/applications/{$application->id}/decide", ['decision' => 'maybe'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('submitted', $application->refresh()->status);
    }

    public function test_converting_an_approved_application_creates_a_student_and_enrols_them(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
        $gradeLevel = GradeLevel::factory()->create([
            'school_id' => $school->id,
            'name_en' => 'Grade 4',
            'name_ar' => 'الصف الرابع',
            'level' => 4,
        ]);
        $section = Section::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        $application = $this->application($school, [
            'status' => 'approved',
            'grade_applying' => 'Grade 4',
        ]);

        $this->post("/admissions/applications/{$application->id}/convert")->assertRedirect();

        $application->refresh();

        $this->assertSame('converted', $application->status);
        $this->assertNotNull($application->converted_student_id);

        $student = Student::findOrFail($application->converted_student_id);
        $this->assertSame($school->id, $student->school_id);
        $this->assertSame('Nour', $student->first_name);
        $this->assertSame('Rahman', $student->last_name);
        $this->assertSame('active', $student->status);

        // The guardian is matched by email and linked, and the student is placed
        // in the section of the grade on the application.
        $guardian = Guardian::where('school_id', $school->id)->where('email', 'amal@example.test')->firstOrFail();
        $this->assertSame('Amal', $guardian->first_name);

        $this->assertDatabaseHas('guardian_relationships', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'is_financial_guardian' => true,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('admission_application_events', [
            'admission_application_id' => $application->id,
            'event_type' => 'converted',
        ]);

        // `new_values` is cast to an array on the model, so it has to be handed
        // an array: encoding it by hand stored a JSON string *inside* JSON and
        // readers got a string where the cast promised an array.
        $log = AuditLog::where('entity_id', $application->id)
            ->where('action', 'admission_converted')
            ->firstOrFail();

        $this->assertSame(['student_id' => $student->id], $log->new_values);
    }

    public function test_converting_twice_is_refused_rather_than_creating_a_second_student(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school, ['status' => 'approved']);

        $this->post("/admissions/applications/{$application->id}/convert")->assertRedirect();

        $this->assertDatabaseCount('students', 1);

        // The second attempt sees a `converted` application, so it never reaches
        // the transaction.
        $this->post("/admissions/applications/{$application->id}/convert")
            ->assertNotFound();

        $this->assertDatabaseCount('students', 1);
    }

    public function test_an_unapproved_application_cannot_be_converted(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($school);

        $this->post("/admissions/applications/{$application->id}/convert")->assertNotFound();

        $this->assertSame(0, Student::count());
        $this->assertSame('submitted', $application->refresh()->status);
    }

    public function test_another_schools_application_cannot_be_converted(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $this->actingAsSchoolUser($school, ['manage-admissions']);

        $application = $this->application($other, ['status' => 'approved']);

        $this->post("/admissions/applications/{$application->id}/convert")->assertNotFound();

        $this->assertSame(0, Student::count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function application(School $school, array $overrides = []): AdmissionApplication
    {
        $application = AdmissionApplication::create(array_merge([
            'school_id' => $school->id,
            'reference' => 'APP-'.fake()->unique()->numberBetween(100000, 999999),
            'status' => 'submitted',
            'guardian_first_name' => 'Amal',
            'guardian_last_name' => 'Rahman',
            'guardian_email' => 'amal@example.test',
            'guardian_phone' => '0500000000',
            'guardian_relationship' => 'mother',
            'student_first_name' => 'Nour',
            'student_last_name' => 'Rahman',
            'student_date_of_birth' => '2016-04-01',
            'student_gender' => 'female',
            'student_nationality' => 'Saudi',
            'submitted_at' => now(),
        ], $overrides));

        // The helper above is a plain create(); the timestamps are what the list
        // sorts by, and `submitted_at` may have been overridden.
        $application->forceFill(['created_at' => $application->submitted_at])->save();

        return $application;
    }
}
