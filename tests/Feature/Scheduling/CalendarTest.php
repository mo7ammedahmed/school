<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Semester;
use App\Domain\Scheduling\Models\CalendarDay;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-16 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_calendar_page_loads(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $this->get('/calendar')->assertOk();
    }

    public function test_the_calendar_page_supports_week_and_day_views(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $this->get('/calendar?view=week&date=2026-09-16')->assertOk();
        $this->get('/calendar?view=day&date=2026-09-16')->assertOk();
    }

    public function test_the_calendar_page_falls_back_to_month_for_unknown_views(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $this->get('/calendar?view=decade')->assertOk();
    }

    public function test_the_feed_aggregates_authored_and_derived_items(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $year = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
        ]);

        Semester::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name_en' => 'Term 1',
            'name_ar' => 'الفصل الأول',
            'code' => 'T1',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-20',
        ]);

        CalendarDay::create([
            'school_id' => $school->id,
            'date' => '2026-09-16',
            'type' => 'holiday',
            'title' => 'National Day',
        ]);

        $response = $this->getJson('/calendar/feed?start=2026-09-16&end=2026-09-16');

        $response->assertOk();
        $response->assertJsonPath('start', '2026-09-16');

        $titles = array_column($response->json('items'), 'title');
        $this->assertContains('National Day', $titles);
        $this->assertContains('Term 1', $titles);

        $types = array_column($response->json('items'), 'type');
        $this->assertContains('holiday', $types);
        $this->assertContains('term', $types);
    }

    public function test_the_feed_can_be_filtered_by_type(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        CalendarDay::create([
            'school_id' => $school->id,
            'date' => '2026-09-16',
            'type' => 'holiday',
            'title' => 'National Day',
        ]);

        CalendarDay::create([
            'school_id' => $school->id,
            'date' => '2026-09-17',
            'type' => 'staff_workday',
            'title' => 'Staff training',
        ]);

        $response = $this->getJson('/calendar/feed?start=2026-09-16&end=2026-09-17&types[]=holiday');

        $response->assertOk();

        $titles = array_column($response->json('items'), 'title');
        $this->assertSame(['National Day'], $titles);
    }

    public function test_it_never_leaks_another_schools_calendar(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        CalendarDay::create([
            'school_id' => $otherSchool->id,
            'date' => '2026-09-16',
            'type' => 'holiday',
            'title' => 'Other school holiday',
        ]);

        $response = $this->getJson('/calendar/feed?start=2026-09-16&end=2026-09-16');

        $response->assertOk();
        $this->assertSame([], $response->json('items'));
    }

    public function test_it_downloads_an_ical_feed(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        CalendarDay::create([
            'school_id' => $school->id,
            'date' => '2026-09-16',
            'type' => 'holiday',
            'title' => 'National Day',
        ]);

        $response = $this->get('/calendar/export.ics?start=2026-09-16&end=2026-09-16');

        $response->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('SUMMARY:National Day', $response->getContent());
    }

    public function test_an_authorised_user_can_add_a_calendar_day(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $response = $this->post('/calendar-days', [
            'title' => 'Founding Day',
            'type' => 'holiday',
            'date' => '2026-09-20',
            'end_date' => '2026-09-21',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('calendar_days', [
            'school_id' => $school->id,
            'title' => 'Founding Day',
            'type' => 'holiday',
        ]);

        $day = CalendarDay::first();
        $this->assertSame(2, (int) $day->date->diffInDays($day->end_date) + 1);
    }

    public function test_a_user_without_permission_cannot_add_a_calendar_day(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->post('/calendar-days', [
            'title' => 'Founding Day',
            'type' => 'holiday',
            'date' => '2026-09-20',
        ])->assertForbidden();

        $this->assertDatabaseCount('calendar_days', 0);
    }

    public function test_the_end_date_must_not_precede_the_start_date(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $this->post('/calendar-days', [
            'title' => 'Backwards',
            'type' => 'holiday',
            'date' => '2026-09-20',
            'end_date' => '2026-09-19',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_an_unknown_calendar_day_type_is_rejected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $this->post('/calendar-days', [
            'title' => 'Nonsense',
            'type' => 'not-a-real-type',
            'date' => '2026-09-20',
        ])->assertSessionHasErrors('type');
    }

    public function test_a_calendar_day_can_be_updated_and_deleted(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $day = CalendarDay::create([
            'school_id' => $school->id,
            'date' => '2026-09-20',
            'type' => 'holiday',
            'title' => 'Original',
        ]);

        $this->put("/calendar-days/{$day->id}", [
            'title' => 'Renamed',
            'type' => 'closure',
            'date' => '2026-09-20',
        ])->assertRedirect();

        $this->assertDatabaseHas('calendar_days', ['id' => $day->id, 'title' => 'Renamed', 'type' => 'closure']);

        $this->delete("/calendar-days/{$day->id}")->assertRedirect();

        $this->assertSoftDeleted('calendar_days', ['id' => $day->id]);
    }

    public function test_it_cannot_update_another_schools_calendar_day(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-calendar']);

        $day = CalendarDay::create([
            'school_id' => $otherSchool->id,
            'date' => '2026-09-20',
            'type' => 'holiday',
            'title' => 'Theirs',
        ]);

        // 404 through the tenant-aware binding: the day does not exist for this
        // school, so the route does not confirm it exists for another one.
        $this->put("/calendar-days/{$day->id}", [
            'title' => 'Hijacked',
            'type' => 'holiday',
            'date' => '2026-09-20',
        ])->assertNotFound();
    }

    public function test_holidays_are_not_teaching_days_by_default(): void
    {
        $school = School::factory()->create();

        $holiday = CalendarDay::create([
            'school_id' => $school->id,
            'date' => '2026-09-20',
            'type' => 'holiday',
            'title' => 'National Day',
        ]);

        $this->assertFalse($holiday->fresh()->is_instructional);
    }

    /**
     * @param  list<string>  $permissions
     */
}
