<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\Admissions\Services\AdmissionReviewService;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The public application journey used to collect five steps of answers into the
 * session and then stop: the review screen rendered a hard-coded applicant and
 * its "Submit Application" button was a link to the thank-you page, so no
 * application was ever created. The guardian and student steps also shared the
 * keys `first_name`, `last_name` and `address`, and the flat merge let the
 * student's answers overwrite the guardian's.
 */
class PublicApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_walk_the_journey_and_the_application_is_stored(): void
    {
        Storage::fake('local');

        $school = School::factory()->create(['slug' => 'home-school']);
        AdmissionPeriod::create([
            'school_id' => $school->id,
            'name' => 'Autumn intake',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $this->walkTheJourney();

        $this->get('/apply/review')->assertOk()->assertInertia(fn ($page) => $page
            ->component('apply/review')
            ->where('collected.guardian.first_name', 'Amal')
            ->where('collected.student.first_name', 'Nour')
            ->has('collected.documents.files', 4)
        );

        $this->post('/apply/submit')->assertRedirect(route('apply.submitted'));

        $application = AdmissionApplication::firstOrFail();

        $this->assertSame($school->id, $application->school_id);
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertNotSame('', $application->reference);

        // The guardian's answers are the guardian's, even though the student step
        // asks for the same field names.
        $this->assertSame('Amal', $application->guardian_first_name);
        $this->assertSame('Rahman', $application->guardian_last_name);
        $this->assertSame('amal@example.test', $application->guardian_email);
        $this->assertSame('Riyadh, Olaya', $application->guardian_address);
        $this->assertSame('Engineer', $application->guardian_occupation);
        $this->assertSame('Nour', $application->student_first_name);
        $this->assertSame('Jeddah, Andalus', $application->student_address);
        $this->assertSame('Alpha Elementary', $application->previous_school_name);
        $this->assertSame('Relocating', $application->reason_for_leaving);

        // The uploaded files are stored, not just named.
        $documents = $application->documents;
        $this->assertCount(4, $documents);
        Storage::disk('local')->assertExists($documents[0]['path']);

        // And the school can actually see the application in its review queue.
        $queue = app(AdmissionReviewService::class)->getReviewQueue($school->id);
        $this->assertSame([$application->id], $queue->pluck('id')->all());
    }

    public function test_the_family_is_given_the_reference_to_quote(): void
    {
        Storage::fake('local');

        School::factory()->create(['slug' => 'home-school']);

        $this->walkTheJourney();
        $this->post('/apply/submit')->assertRedirect(route('apply.submitted'));

        $reference = AdmissionApplication::firstOrFail()->reference;

        $this->get('/apply/submitted')->assertOk()->assertInertia(fn ($page) => $page
            ->component('apply/submitted')
            ->where('reference', $reference)
        );
    }

    public function test_the_school_sees_what_the_family_wrote_on_the_review_screen(): void
    {
        Storage::fake('local');

        $school = School::factory()->create(['slug' => 'home-school']);

        $this->walkTheJourney();
        $this->post('/apply/submit');

        $application = AdmissionApplication::firstOrFail();

        $this->actingAsSchoolUser($school, ['manage-admissions']);

        // The five answers that used to have nowhere to live are on the screen
        // the admissions office actually reads.
        $this->get("/admissions/review/{$application->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('admissions/review/show')
            ->where('application.guardian_occupation', 'Engineer')
            ->where('application.guardian_address', 'Riyadh, Olaya')
            ->where('application.student_address', 'Jeddah, Andalus')
            ->where('application.previous_school_address', 'Dammam')
            ->where('application.reason_for_leaving', 'Relocating')
        );
    }

    public function test_submitting_without_completing_the_steps_sends_the_visitor_back_to_the_start(): void
    {
        School::factory()->create(['slug' => 'home-school']);

        $this->post('/apply/submit')
            ->assertRedirect(route('apply.start'))
            ->assertSessionHas('error');

        $this->assertSame(0, AdmissionApplication::count());
    }

    public function test_a_missing_step_is_rejected_rather_than_half_saved(): void
    {
        School::factory()->create(['slug' => 'home-school']);

        $this->post('/apply/guardian', ['first_name' => 'Amal'])
            ->assertSessionHasErrors(['last_name', 'email', 'phone', 'relationship', 'address']);

        $this->assertSame(0, AdmissionApplication::count());
    }

    private function walkTheJourney(): void
    {
        $this->post('/apply/start', ['academic_year_id' => 1])
            ->assertRedirect(route('apply.guardian'));

        $this->post('/apply/guardian', [
            'first_name' => 'Amal',
            'last_name' => 'Rahman',
            'email' => 'amal@example.test',
            'phone' => '0500000000',
            'relationship' => 'mother',
            'occupation' => 'Engineer',
            'address' => 'Riyadh, Olaya',
        ])->assertRedirect(route('apply.student'));

        $this->post('/apply/student', [
            'first_name' => 'Nour',
            'last_name' => 'Rahman',
            'date_of_birth' => '2016-04-01',
            'gender' => 'female',
            'nationality' => 'Saudi',
            'address' => 'Jeddah, Andalus',
        ])->assertRedirect(route('apply.previous-school'));

        $this->post('/apply/previous-school', [
            'school_name' => 'Alpha Elementary',
            'school_address' => 'Dammam',
            'last_grade_completed' => 'Grade 4',
            'reason_for_leaving' => 'Relocating',
        ])->assertRedirect(route('apply.documents'));

        $this->post('/apply/documents', [
            'birth_certificate' => UploadedFile::fake()->create('birth.pdf', 20),
            'previous_school_records' => UploadedFile::fake()->create('records.pdf', 20),
            'passport_photos' => UploadedFile::fake()->create('photos.jpg', 20),
            'medical_records' => UploadedFile::fake()->create('medical.pdf', 20),
        ])->assertRedirect(route('apply.review'));
    }
}
