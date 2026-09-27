<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Enrollment;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Scheduling\Models\Period;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private Section $section;

    private Subject $subject;

    private TeacherProfile $teacher;

    private Offering $offering;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);

        $gradeLevel = GradeLevel::create([
            'school_id' => $this->school->id,
            'name_en' => 'Grade 5',
            'code' => 'G5',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name_en' => 'Grade 5A',
            'code' => 'G5A',
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name_en' => 'Mathematics',
            'name_ar' => 'الرياضيات',
            'code' => 'MATH',
        ]);

        $this->teacher = TeacherProfile::create([
            'school_id' => $this->school->id,
            'first_name' => 'Layla',
            'last_name' => 'Hassan',
        ]);

        $this->room = Room::create([
            'school_id' => $this->school->id,
            'name_en' => 'Room 101',
            'code' => 'R101',
            'room_type' => 'classroom',
            'capacity' => 30,
        ]);

        $this->offering = Offering::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics · Grade 5A',
        ]);
    }

    public function test_the_grid_page_loads(): void
    {
        $this->actingAsSchoolUser();

        $this->get('/timetable/grid')->assertOk();
        $this->get("/timetable/grid?section_id={$this->section->id}")->assertOk();
    }

    public function test_the_grid_falls_back_to_derived_rows_when_no_periods_exist(): void
    {
        $this->actingAsSchoolUser();
        $this->entry();

        $response = $this->get('/timetable/grid');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertFalse($page['props']['hasPeriods']);
        $this->assertSame('09:00 - 10:00', $page['props']['rows'][0]['label']);
    }

    public function test_the_grid_aligns_entries_to_bell_schedule_rows(): void
    {
        $this->actingAsSchoolUser();

        Period::create([
            'school_id' => $this->school->id,
            'name_en' => 'First period',
            'code' => 'P1',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'sort_order' => 1,
        ]);

        $this->entry();

        $response = $this->get('/timetable/grid');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertTrue($page['props']['hasPeriods']);
        $this->assertSame('First period', $page['props']['rows'][0]['label']);
        $this->assertCount(1, $page['props']['matrix']['sunday'][0]);
        $this->assertSame('Mathematics', $page['props']['matrix']['sunday'][0][0]['subject']);
    }

    public function test_creating_an_entry_publishes_nothing_by_default(): void
    {
        $this->actingAsSchoolUser();

        $this->post('/timetable', [
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'Room 101',
        ])->assertRedirect();

        $this->assertDatabaseHas('timetable_entries', [
            'school_id' => $this->school->id,
            'is_published' => false,
        ]);
    }

    public function test_an_overlapping_entry_is_rejected(): void
    {
        $this->actingAsSchoolUser();
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $this->post('/timetable', [
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:30',
            'end_time' => '10:30',
            'room' => 'Room 101',
        ])->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('timetable_entries', 1);
    }

    public function test_a_back_to_back_entry_is_accepted(): void
    {
        $this->actingAsSchoolUser();
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $this->post('/timetable', [
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'sunday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'room' => 'Room 101',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('timetable_entries', 2);
    }

    public function test_entries_cannot_reference_another_schools_resources(): void
    {
        $this->actingAsSchoolUser();

        $otherSchool = School::factory()->create();
        $otherSubject = Subject::create(['school_id' => $otherSchool->id, 'name_en' => 'Foreign']);

        $this->post('/timetable', [
            'subject_id' => $otherSubject->id,
            'section_id' => $this->section->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ])->assertSessionHasErrors('subject_id');
    }

    public function test_an_entry_can_be_published_and_unpublished(): void
    {
        $this->actingAsSchoolUser();
        $entry = $this->entry();

        $this->post("/timetable/{$entry->id}/publish")->assertRedirect();
        $this->assertTrue($entry->fresh()->is_published);

        $this->post("/timetable/{$entry->id}/unpublish")->assertRedirect();
        $this->assertFalse($entry->fresh()->is_published);
    }

    public function test_it_refuses_to_mutate_another_schools_entry(): void
    {
        $this->actingAsSchoolUser();

        $otherSchool = School::factory()->create();
        $otherEntry = TimetableEntry::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $this->academicYear->id,
            'offering_id' => $this->offering->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $this->post("/timetable/{$otherEntry->id}/publish")->assertForbidden();
    }

    public function test_the_conflicts_page_loads(): void
    {
        $this->actingAsSchoolUser();
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);
        $this->entry(['start_time' => '09:30', 'end_time' => '10:30']);

        $response = $this->get('/timetable/conflicts');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertCount(1, $page['props']['conflicts']);
    }

    public function test_the_pdf_export_returns_a_pdf(): void
    {
        $this->actingAsSchoolUser();
        $this->entry();

        $response = $this->get('/timetable/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_the_arabic_pdf_export_carries_joined_arabic_glyphs(): void
    {
        $this->actingAsSchoolUser();
        $this->entry();

        // The locale is decided per request (session, then profile), so the test
        // asks for Arabic the same way the app does.
        $this->withSession(['locale' => 'ar']);
        $this->school->update(['name_ar' => 'مدرسة النور']);

        $response = $this->get('/timetable/export/pdf');
        $response->assertOk();

        $points = $this->shapedCodePoints($response->getContent());

        // Arabic Presentation Forms-B: the sheet was drawn with positional
        // glyphs, not with the unjoined base letters dompdf would have used.
        $joined = array_filter($points, fn (int $point): bool => $point >= 0xFE70 && $point <= 0xFEFC);

        $this->assertNotEmpty($joined, 'The Arabic timetable PDF embedded no shaped glyphs.');
    }

    /**
     * Every code point the PDF draws, read back out of its content stream.
     *
     * dompdf writes text as UTF-16BE runs (`[(..)..] TJ`) inside a compressed
     * stream, so the streams are inflated and the runs decoded.
     *
     * @return list<int>
     */
    private function shapedCodePoints(string $pdf): array
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);

        $points = [];

        foreach ($streams[1] as $stream) {
            $inflated = @gzuncompress($stream);
            $text = $inflated === false ? $stream : $inflated;

            preg_match_all('/\[\((.*?)\)\]\s*TJ/s', $text, $runs);

            foreach ($runs[1] as $run) {
                if (strlen($run) % 2 !== 0) {
                    continue;
                }

                $decoded = mb_convert_encoding($run, 'UTF-8', 'UTF-16BE');

                foreach (preg_split('//u', $decoded, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
                    $points[] = mb_ord($char, 'UTF-8');
                }
            }
        }

        return $points;
    }

    public function test_the_ics_export_returns_a_calendar(): void
    {
        $this->actingAsSchoolUser();
        $this->entry();

        $response = $this->get('/timetable/export/ics');

        $response->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('RRULE:FREQ=WEEKLY', $response->getContent());
    }

    public function test_the_student_schedule_only_shows_published_entries(): void
    {
        $published = $this->entry(['start_time' => '09:00', 'end_time' => '10:00', 'is_published' => true]);
        $draft = $this->entry(['start_time' => '11:00', 'end_time' => '12:00', 'is_published' => false]);

        $studentUser = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $studentUser->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
        ]);

        // The section relation is membership through active enrollments.
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'enrollment_date' => '2026-09-01',
            'status' => 'active',
        ]);

        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $studentUser->assignRole('student');

        $this->actingAs($studentUser);
        $this->app['session']->put('school_id', $this->school->id);

        $response = $this->get('/student/schedule');
        $response->assertOk();

        $ids = collect($response->viewData('page')['props']['timetable'])->pluck('id');

        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($draft->id));
    }

    private function actingAsSchoolUser(): User
    {
        Permission::firstOrCreate(['name' => 'view-own-schedule', 'guard_name' => 'web']);

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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function entry(array $overrides = []): TimetableEntry
    {
        return TimetableEntry::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'offering_id' => $this->offering->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'room_id' => $this->room->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            ...$overrides,
        ]);
    }
}
