<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Scheduling\Services\TimetableConflictDetector;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableConflictDetectorTest extends TestCase
{
    use RefreshDatabase;

    private TimetableConflictDetector $detector;

    private School $school;

    private AcademicYear $academicYear;

    private Section $section;

    private TeacherProfile $teacher;

    private Room $room;

    private Offering $offering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(TimetableConflictDetector::class);

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);

        $gradeLevel = GradeLevel::create([
            'school_id' => $this->school->id,
            'name_en' => 'Grade 5',
            'name_ar' => 'الصف الخامس',
            'code' => 'G5',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name_en' => 'Grade 5A',
            'name_ar' => 'الصف الخامس أ',
            'code' => 'G5A',
        ]);

        $subject = Subject::create([
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
            'name_ar' => 'غرفة ١٠١',
            'code' => 'R101',
            'room_type' => 'classroom',
            'capacity' => 30,
        ]);

        $this->offering = Offering::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics · Grade 5A',
        ]);
    }

    public function test_it_detects_a_teacher_double_booking(): void
    {
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $conflicts = $this->detector->detect($this->candidate(['start_time' => '09:30', 'end_time' => '10:30']));

        $this->assertCount(1, $conflicts);
        $this->assertContains('teacher', $conflicts[0]['reasons']);
    }

    public function test_it_detects_a_room_double_booking(): void
    {
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $conflicts = $this->detector->detect($this->candidate(['start_time' => '09:30', 'end_time' => '10:30']));

        $this->assertCount(1, $conflicts);
        $this->assertContains('room', $conflicts[0]['reasons']);
    }

    public function test_it_detects_a_section_double_booking(): void
    {
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $conflicts = $this->detector->detect($this->candidate(['start_time' => '09:30', 'end_time' => '10:30']));

        $this->assertCount(1, $conflicts);
        $this->assertContains('section', $conflicts[0]['reasons']);
    }

    public function test_back_to_back_periods_do_not_conflict(): void
    {
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $conflicts = $this->detector->detect($this->candidate(['start_time' => '10:00', 'end_time' => '11:00']));

        $this->assertSame([], $conflicts);
    }

    public function test_entries_on_another_day_do_not_conflict(): void
    {
        $this->entry(['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00']);

        $conflicts = $this->detector->detect($this->candidate(['day_of_week' => 'tuesday']));

        $this->assertSame([], $conflicts);
    }

    public function test_entries_in_another_school_are_ignored(): void
    {
        $otherSchool = School::factory()->create();
        $otherYear = AcademicYear::factory()->create(['school_id' => $otherSchool->id]);
        $otherGrade = GradeLevel::create(['school_id' => $otherSchool->id, 'name_en' => 'Grade 5', 'code' => 'G5']);
        $otherSection = Section::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'grade_level_id' => $otherGrade->id,
            'name_en' => 'Other 5A',
        ]);
        $otherSubject = Subject::create(['school_id' => $otherSchool->id, 'name_en' => 'Maths']);
        $otherTeacher = TeacherProfile::create([
            'school_id' => $otherSchool->id,
            'first_name' => 'Other',
            'last_name' => 'Teacher',
        ]);
        $otherOffering = Offering::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $otherTeacher->id,
            'section_id' => $otherSection->id,
            'name' => 'Other',
        ]);

        TimetableEntry::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'offering_id' => $otherOffering->id,
            'teacher_id' => $otherTeacher->id,
            'section_id' => $otherSection->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $this->assertSame([], $this->detector->detect($this->candidate()));
    }

    public function test_the_entry_being_updated_is_excluded(): void
    {
        $existing = $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);

        $conflicts = $this->detector->detect($this->candidate(), $existing->id);

        $this->assertSame([], $conflicts);
    }

    public function test_a_candidate_with_no_shared_resources_never_conflicts(): void
    {
        $this->entry();

        $conflicts = $this->detector->detect($this->candidate([
            'teacher_id' => null,
            'room_id' => null,
            'section_id' => null,
        ]));

        $this->assertSame([], $conflicts);
    }

    public function test_invalid_time_ranges_are_ignored(): void
    {
        $conflicts = $this->detector->detect($this->candidate(['start_time' => 'nonsense', 'end_time' => '10:00']));

        $this->assertSame([], $conflicts);
    }

    public function test_all_for_school_reports_each_conflicting_pair(): void
    {
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);
        $this->entry(['start_time' => '09:30', 'end_time' => '10:30']);

        $conflicts = $this->detector->allForSchool($this->school->id);

        $this->assertCount(1, $conflicts);
        $this->assertSame('sunday', $conflicts[0]['day_of_week']);
        $this->assertEqualsCanonicalizing(['teacher', 'room', 'section'], $conflicts[0]['reasons']);
    }

    public function test_all_for_school_is_empty_for_a_clean_timetable(): void
    {
        $this->entry(['start_time' => '09:00', 'end_time' => '10:00']);
        $this->entry(['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00']);

        $this->assertSame([], $this->detector->allForSchool($this->school->id));
    }

    public function test_it_parses_time_strings_in_every_supported_shape(): void
    {
        $this->assertSame(540, $this->detector->minutes('09:00'));
        $this->assertSame(540, $this->detector->minutes('09:00:00'));
        $this->assertSame(0, $this->detector->minutes('00:00'));
        $this->assertNull($this->detector->minutes('24:00'));
        $this->assertNull($this->detector->minutes('09:70'));
        $this->assertNull($this->detector->minutes(''));
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function candidate(array $overrides = []): array
    {
        return [
            'school_id' => $this->school->id,
            'day_of_week' => 'sunday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'section_id' => $this->section->id,
            ...$overrides,
        ];
    }
}
