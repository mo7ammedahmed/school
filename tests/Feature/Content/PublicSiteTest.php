<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\Content\Models\Event;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public site is the one part of the app nobody signs in to test.
 *
 * Every page below was broken in a way that could only be seen by loading it:
 * news and events filtered on columns that do not exist (`publish_date`,
 * `event_date`), so articles 404ed and listings were empty; the news and event
 * cards read `date`, `time` and `category`, so every card printed "Invalid
 * Date"; the teachers page asked for a table, a relation and three columns that
 * do not exist; and the programs and admissions pages read `name`, which the
 * database returned as the literal word "name".
 *
 * These tests load the pages the way a visitor does — signed out — and check the
 * values the screens actually print.
 */
class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_news_list_shows_published_articles_and_hides_scheduled_ones(): void
    {
        $school = School::factory()->create();

        $published = $this->article($school, 'Open day', now()->subDay());
        $this->article($school, 'Not yet', now()->addWeek());

        $this->get('/news')->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/news/index')
            ->has('articles.data', 1)
            ->where('articles.data.0.id', $published->id)
            ->where('articles.data.0.publish_date', now()->subDay()->toDateString())
            ->where('articles.data.0.category', 'general')
        );
    }

    public function test_an_article_page_can_be_opened(): void
    {
        $school = School::factory()->create();
        $article = $this->article($school, 'Open day', now()->subDay());

        $this->get("/news/{$article->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/news/show')
            ->where('article.id', $article->id)
            ->where('article.publish_date', now()->subDay()->toDateString())
        );
    }

    public function test_a_scheduled_article_has_no_page_yet(): void
    {
        $school = School::factory()->create();
        $scheduled = $this->article($school, 'Not yet', now()->addWeek());

        $this->get("/news/{$scheduled->id}")->assertNotFound();
    }

    public function test_the_event_list_shows_upcoming_events_with_a_date_and_a_time(): void
    {
        $school = School::factory()->create();

        $upcoming = $this->event($school, 'Science fair', now()->addWeek());
        $this->event($school, 'Last year', now()->subYear());

        $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/events/index')
            ->has('events.data', 1)
            ->where('events.data.0.id', $upcoming->id)
            ->where('events.data.0.event_date', now()->addWeek()->toDateString())
            ->where('events.data.0.start_time', now()->addWeek()->format('H:i'))
            ->where('events.data.0.event_type', 'assembly')
        );
    }

    public function test_a_published_event_keeps_its_page_after_the_date_passes(): void
    {
        $school = School::factory()->create();
        $past = $this->event($school, 'Last year', now()->subYear());

        $this->get("/events/{$past->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/events/show')
            ->where('event.id', $past->id)
            ->where('event.event_date', now()->subYear()->toDateString())
        );
    }

    public function test_the_teachers_page_lists_real_profiles_and_the_subjects_they_teach(): void
    {
        $school = School::factory()->create();
        $teacher = TeacherProfile::factory()->create([
            'school_id' => $school->id,
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'specialization' => 'Mathematics',
        ]);

        $subject = Subject::factory()->create(['school_id' => $school->id, 'name_en' => 'Algebra', 'name_ar' => 'الجبر']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $subject->grade_level_id,
        ]);

        Offering::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'name' => 'Algebra',
        ]);

        $this->get('/faculty')->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/teachers')
            ->has('teachers', 1)
            ->where('teachers.0.specialization', 'Mathematics')
        );

        $this->get("/faculty/{$teacher->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/teachers/show')
            ->where('teacher.specialization', 'Mathematics')
            ->has('teacher.subjects', 1)
            // Objects, not ids: the page used to render each one as a child.
            ->where('teacher.subjects.0.name', 'Algebra')
        );
    }

    public function test_the_programs_page_shows_names_rather_than_the_word_name(): void
    {
        $school = School::factory()->create();
        $gradeLevel = GradeLevel::factory()->create([
            'school_id' => $school->id,
            'name_en' => 'Grade 4',
            'name_ar' => 'الصف الرابع',
            'level' => 4,
        ]);

        Subject::factory()->create([
            'school_id' => $school->id,
            'name_en' => 'Mathematics',
            'name_ar' => 'الرياضيات',
            'grade_level_id' => $gradeLevel->id,
        ]);

        $this->get('/programs')->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/programs')
            ->has('programs', 1)
            ->where('programs.0.name', 'Mathematics')
            ->where('programs.0.grade_level.name', 'Grade 4')
            ->has('gradeLevels', 1)
            ->where('gradeLevels.0.name', 'Grade 4')
        );
    }

    public function test_the_admissions_page_shows_the_periods_that_are_open(): void
    {
        $school = School::factory()->create();

        AdmissionPeriod::create([
            'school_id' => $school->id,
            'name' => 'Autumn intake',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        GradeLevel::factory()->create(['school_id' => $school->id, 'name_en' => 'Grade 1', 'name_ar' => 'الصف الأول', 'level' => 1]);

        $this->get('/admissions')->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/admissions')
            ->has('periods', 1)
            ->where('periods.0.name', 'Autumn intake')
            ->has('gradeLevels', 1)
        );
    }

    private function article(School $school, string $title, \DateTimeInterface $publishedAt): News
    {
        return News::create([
            'school_id' => $school->id,
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 9999),
            'category' => 'general',
            'excerpt' => 'An excerpt',
            'content' => 'The article',
            'is_published' => true,
            'published_at' => $publishedAt,
        ]);
    }

    private function event(School $school, string $title, \DateTimeInterface $startsAt): Event
    {
        return Event::create([
            'school_id' => $school->id,
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 9999),
            'description' => 'What happens',
            'start_date' => $startsAt,
            'end_date' => $startsAt,
            'location' => 'The hall',
            'event_type' => 'assembly',
            'is_published' => true,
        ]);
    }
}
