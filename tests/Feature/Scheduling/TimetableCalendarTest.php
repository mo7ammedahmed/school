<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Scheduling\Models\CalendarDay;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableCalendarTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Offering $offering;

    private Section $section;

    private TeacherProfile $teacher;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        $academicYear = $this->academicYear;

        $gradeLevel = GradeLevel::create([
            'school_id' => $this->school->id,
            'name_en' => 'Grade 6',
            'code' => 'G6',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name_en' => 'Grade 6A',
            'code' => 'G6A',
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name_en' => 'Science',
            'name_ar' => 'العلوم',
            'code' => 'SCI',
        ]);

        $this->teacher = TeacherProfile::create([
            'school_id' => $this->school->id,
            'first_name' => 'Omar',
            'last_name' => 'Saleh',
        ]);

        $room = Room::create([
            'school_id' => $this->school->id,
            'name_en' => 'Lab 1',
            'code' => 'LAB1',
            'room_type' => 'laboratory',
            'capacity' => 24,
        ]);

        $this->offering = Offering::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'name' => 'Science · Grade 6A',
        ]);

        TimetableEntry::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'offering_id' => $this->offering->id,
            'section_id' => $this->section->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $room->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_published' => true,
        ]);

        $this->actingAsSchoolUser();
    }

    public function test_lessons_are_projected_onto_each_matching_weekday(): void
    {
        $response = $this->get('/timetable/calendar?date=2026-09-01');
        $response->assertOk();

        $cells = collect($response->viewData('page')['props']['cells']);

        $sunday = $cells->firstWhere('date', '2026-09-06');
        $this->assertNotNull($sunday);
        $this->assertTrue($sunday['is_school_day']);
        $this->assertCount(1, $sunday['lessons']);
        $this->assertSame('Science', $sunday['lessons'][0]['subject']);
        $this->assertSame('09:00', $sunday['lessons'][0]['start']);

        // Every Sunday inside September carries the same recurring lesson.
        $sundaysInMonth = $cells->filter(
            fn (array $cell) => $cell['in_month'] && $cell['weekday'] === 'sunday'
        );
        $this->assertCount(4, $sundaysInMonth);
        $this->assertTrue($sundaysInMonth->every(fn (array $cell) => count($cell['lessons']) === 1));
    }

    public function test_the_timetable_lands_on_the_calendar_view(): void
    {
        $response = $this->get('/timetable');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertSame('timetable/calendar', $page['component']);
        $this->assertSame('/timetable', $page['props']['basePath']);
        $this->assertNotNull($page['props']['month']);
        $this->assertNotEmpty($page['props']['cells']);
    }

    public function test_the_list_view_still_renders_the_table(): void
    {
        $response = $this->get('/timetable/list');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertSame('timetable/index', $page['component']);
        $this->assertCount(1, $page['props']['timetables']['data']);
    }

    public function test_list_filters_narrow_the_results(): void
    {
        $otherSection = Section::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->section->grade_level_id,
            'name_en' => 'Grade 6B',
            'code' => 'G6B',
        ]);

        $this->assertSame(1, $this->get('/timetable/list')->viewData('page')['props']['timetables']['total']);
        $this->assertSame(1, $this->get("/timetable/list?section_id={$this->section->id}")->viewData('page')['props']['timetables']['total']);
        $this->assertSame(0, $this->get("/timetable/list?section_id={$otherSection->id}")->viewData('page')['props']['timetables']['total']);
    }

    public function test_calendar_filters_are_reflected_in_the_props(): void
    {
        $response = $this->get("/timetable?section_id={$this->section->id}&teacher_id={$this->teacher->id}");
        $response->assertOk();

        $filters = $response->viewData('page')['props']['filters'];
        $this->assertSame($this->section->id, $filters['section_id']);
        $this->assertSame($this->teacher->id, $filters['teacher_id']);
    }

    public function test_weekend_days_never_carry_lessons(): void
    {
        $response = $this->get('/timetable/calendar?date=2026-09-01');
        $cells = collect($response->viewData('page')['props']['cells']);

        $friday = $cells->firstWhere('date', '2026-09-04');
        $this->assertFalse($friday['is_school_day']);
        $this->assertSame([], $friday['lessons']);
    }

    public function test_a_holiday_suppresses_the_lessons_on_that_date(): void
    {
        CalendarDay::create([
            'school_id' => $this->school->id,
            'date' => '2026-09-13',
            'type' => 'holiday',
            'title' => 'National Day',
            'is_instructional' => false,
        ]);

        $response = $this->get('/timetable/calendar?date=2026-09-01');
        $cells = collect($response->viewData('page')['props']['cells']);

        $holiday = $cells->firstWhere('date', '2026-09-13');
        $this->assertSame('National Day', $holiday['closure']);
        $this->assertSame([], $holiday['lessons']);

        // The following Sunday is unaffected.
        $nextSunday = $cells->firstWhere('date', '2026-09-20');
        $this->assertNull($nextSunday['closure']);
        $this->assertCount(1, $nextSunday['lessons']);
    }

    private function actingAsSchoolUser(): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $this->school->id);

        return $user;
    }
}
