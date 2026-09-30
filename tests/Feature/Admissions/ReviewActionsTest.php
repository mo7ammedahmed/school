<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Services\AdmissionReviewService;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Assigning, re-prioritising and annotating an application write `assigned_to`,
 * `priority` and `internal_notes` — three columns no migration created. The
 * queue's filters matched nothing and each button raised "no such column", which
 * no test noticed because nothing exercised the service directly.
 */
class ReviewActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_application_can_be_assigned_prioritised_and_annotated(): void
    {
        $school = School::factory()->create();
        $reviewer = User::factory()->create();
        $application = $this->application($school);

        $service = app(AdmissionReviewService::class);

        $service->assignApplication($application, $reviewer->id, $reviewer);
        $service->updatePriority($application, 'urgent', $reviewer);
        $service->addInternalNotes($application, 'Guardian asked for a callback.', $reviewer);

        $application->refresh();

        $this->assertSame($reviewer->id, $application->assigned_to);
        $this->assertSame('urgent', $application->priority);
        $this->assertStringContainsString('Guardian asked for a callback.', (string) $application->internal_notes);

        $this->assertDatabaseHas('admission_application_events', [
            'admission_application_id' => $application->id,
            'event_type' => 'internal_note_added',
        ]);
    }

    public function test_adding_a_second_note_keeps_the_first(): void
    {
        $school = School::factory()->create();
        $reviewer = User::factory()->create();
        $application = $this->application($school);

        $service = app(AdmissionReviewService::class);
        $service->addInternalNotes($application, 'First note', $reviewer);
        $service->addInternalNotes($application, 'Second note', $reviewer);

        $notes = (string) $application->refresh()->internal_notes;

        $this->assertStringContainsString('First note', $notes);
        $this->assertStringContainsString('Second note', $notes);
    }

    public function test_the_review_queue_filters_by_priority_and_assignee(): void
    {
        $school = School::factory()->create();
        $reviewer = User::factory()->create();

        $urgent = $this->application($school, ['priority' => 'urgent']);
        $this->application($school);

        $service = app(AdmissionReviewService::class);

        $this->assertSame(
            [$urgent->id],
            $service->getReviewQueue($school->id, ['priority' => 'urgent'])->pluck('id')->all(),
        );

        // Nothing is assigned yet, so the unassigned filter must return both.
        $this->assertCount(2, $service->getReviewQueue($school->id, ['assigned_to' => 'unassigned']));

        $service->assignApplication($urgent, $reviewer->id, $reviewer);

        $this->assertSame(
            [$urgent->id],
            $service->getReviewQueue($school->id, ['assigned_to' => $reviewer->id])->pluck('id')->all(),
        );
        $this->assertCount(1, $service->getReviewQueue($school->id, ['assigned_to' => 'unassigned']));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function application(School $school, array $overrides = []): AdmissionApplication
    {
        return AdmissionApplication::create(array_merge([
            'school_id' => $school->id,
            'reference' => 'APP-'.fake()->unique()->numberBetween(100000, 999999),
            'status' => 'submitted',
            'guardian_first_name' => 'Amal',
            'guardian_last_name' => 'Rahman',
            'guardian_email' => 'amal@example.test',
            'student_first_name' => 'Nour',
            'student_last_name' => 'Rahman',
            'submitted_at' => now(),
        ], $overrides));
    }
}
