<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Models\Event;
use App\Domain\Schools\Models\School;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public site shows the site's own school, and only that school.
 *
 * Every page here used to be unscoped: `/news`, `/events` and the homepage
 * listed every school's rows, and `/news/{post}` happily opened another school's
 * article by id. The single-school tests could not see it, because one school
 * makes an unscoped query look correct.
 *
 * Two schools make the difference visible: the second school's rows must never
 * appear, and its records must not be reachable by id or slug.
 */
class PublicTenancyTest extends TestCase
{
    use RefreshDatabase;

    private School $home;

    private School $other;

    protected function setUp(): void
    {
        parent::setUp();

        // `SchoolResolver` falls back to the lowest id, so the first school
        // created is the one the public site speaks for.
        $this->home = School::factory()->create(['slug' => 'home-school']);
        $this->other = School::factory()->create(['slug' => 'other-school']);
    }

    public function test_the_news_list_shows_only_the_public_schools_articles(): void
    {
        $mine = $this->article($this->home, 'Our open day');
        $this->article($this->other, 'Their open day');

        $this->get('/news')->assertOk()->assertInertia(fn ($page) => $page
            ->has('articles.data', 1)
            ->where('articles.data.0.id', $mine->id)
        );
    }

    public function test_another_schools_article_is_not_reachable_by_id(): void
    {
        $theirs = $this->article($this->other, 'Their open day');

        $this->get("/news/{$theirs->id}")->assertNotFound();
    }

    public function test_the_homepage_shows_only_the_public_schools_articles(): void
    {
        $mine = $this->article($this->home, 'Our open day');
        $this->article($this->other, 'Their open day');

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->has('latestNews', 1)
            ->where('latestNews.0.id', $mine->id)
        );
    }

    public function test_the_event_list_shows_only_the_public_schools_events(): void
    {
        $mine = $this->event($this->home, 'Our science fair');
        $this->event($this->other, 'Their science fair');

        $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $mine->id)
        );
    }

    public function test_another_schools_event_is_not_reachable_by_id(): void
    {
        $theirs = $this->event($this->other, 'Their science fair');

        $this->get("/events/{$theirs->id}")->assertNotFound();
    }

    public function test_the_homepage_shows_only_the_public_schools_events(): void
    {
        $mine = $this->event($this->home, 'Our science fair');
        $this->event($this->other, 'Their science fair');

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.id', $mine->id)
        );
    }

    public function test_a_cms_page_resolves_inside_the_public_school_only(): void
    {
        ContentPage::create([
            'school_id' => $this->home->id,
            'slug' => 'about-us',
            'title' => 'Our about page',
            'content' => 'Ours',
            'status' => 'published',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        ContentPage::create([
            'school_id' => $this->other->id,
            'slug' => 'about-them',
            'title' => 'Their about page',
            'content' => 'Theirs',
            'status' => 'published',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/pages/about-us')->assertOk()->assertInertia(fn ($page) => $page
            ->where('page.title', 'Our about page')
        );

        $this->get('/pages/about-them')->assertNotFound();
    }

    private function article(School $school, string $title): News
    {
        return News::create([
            'school_id' => $school->id,
            'title' => $title,
            'slug' => str($title)->slug(),
            'category' => 'general',
            'excerpt' => 'An excerpt',
            'content' => 'The article',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    private function event(School $school, string $title): Event
    {
        return Event::create([
            'school_id' => $school->id,
            'title' => $title,
            'slug' => str($title)->slug(),
            'description' => 'What happens',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek(),
            'location' => 'The hall',
            'event_type' => 'assembly',
            'is_published' => true,
        ]);
    }
}
