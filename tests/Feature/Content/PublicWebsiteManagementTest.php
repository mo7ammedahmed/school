<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Models\News;
use App\Domain\Content\Models\StaffProfile;
use App\Domain\Content\Services\PublicWebsiteContent;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWebsiteManagementTest extends TestCase
{
    use RefreshDatabase;

    private function page(School $school, string $slug, array $attributes = []): ContentPage
    {
        return ContentPage::create($attributes + [
            'school_id' => $school->id, 'title' => 'School story', 'title_ar' => 'قصة المدرسة',
            'slug' => $slug, 'content' => 'Our public story', 'content_ar' => 'محتوى المدرسة العام',
            'template' => 'landing', 'status' => 'published', 'is_published' => true,
            'published_at' => now(), 'robots' => 'index,follow', 'sections' => [],
        ]);
    }

    private function payload(array $attributes = []): array
    {
        return $attributes + ['title' => 'School life', 'title_ar' => 'الحياة المدرسية', 'slug' => 'school-life',
            'template' => 'standard', 'status' => 'draft', 'robots' => 'index,follow', 'sections' => []];
    }

    public function test_every_main_public_page_can_use_managed_content(): void
    {
        $school = School::factory()->create();
        foreach (app(PublicWebsiteContent::class)->locations() as $location) {
            $this->page($school, $location['slug']);
            $this->get($location['url'])->assertOk()->assertInertia(fn ($page) => $page
                ->component('public/page')->where('page.slug', $location['slug'])
                ->where('page.content_ar', 'محتوى المدرسة العام')->where('preview', false)
                ->missing('page.school_id')->missing('page.status')->missing('page.seo_metadata'));
        }
    }

    public function test_an_admin_can_save_bilingual_reordered_sections_and_navigation(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-content']);
        $this->post('/content/pages', $this->payload([
            'content_ar' => 'حياة مدرسية غنية بالتجارب', 'show_in_navigation' => true, 'navigation_order' => 2,
            'sections' => [
                ['type' => 'faq', 'enabled' => true, 'content' => ['items' => [['title_ar' => 'كيف ألتحق؟', 'description_ar' => 'قدّم طلبًا إلكترونيًا.']]], 'settings' => []],
                ['type' => 'hero', 'enabled' => false, 'content' => ['title_ar' => 'قسم مخفي'], 'settings' => []],
            ],
        ]))->assertRedirect();
        $page = ContentPage::where('slug', 'school-life')->firstOrFail();
        $this->assertSame('حياة مدرسية غنية بالتجارب', $page->content_ar);
        $this->assertTrue($page->show_in_navigation);
        $this->assertSame('faq', $page->sections[0]['type']);
        $this->assertFalse($page->sections[1]['enabled']);
        $this->put('/content/pages/'.$page->id, $this->payload(['status' => 'published', 'sections' => $page->sections]))->assertRedirect();
        $this->get('/pages/school-life')->assertOk()->assertInertia(fn ($response) => $response
            ->has('page.sections', 1)->where('page.sections.0.type', 'faq')->missing('collections.faq'));
    }

    public function test_drafts_future_publication_and_archives_never_reach_public_routes_or_navigation(): void
    {
        $school = School::factory()->create();
        $this->page($school, 'draft', ['status' => 'draft', 'is_published' => false, 'show_in_navigation' => true]);
        $this->page($school, 'future', ['published_at' => now()->addDay(), 'show_in_navigation' => true]);
        $this->page($school, 'archived', ['status' => 'archived', 'show_in_navigation' => true]);
        $this->page($school, 'scheduled', ['status' => 'scheduled', 'is_published' => false, 'scheduled_at' => now()->addDay(), 'show_in_navigation' => true]);
        foreach (['draft', 'future', 'archived', 'scheduled'] as $slug) {
            $this->get('/pages/'.$slug)->assertNotFound();
        }
        $this->get('/about')->assertOk()->assertInertia(fn ($response) => $response->has('websiteNavigation', 0));
        $this->travel(2)->days();
        $this->get('/pages/scheduled')->assertOk();
        $this->get('/about')->assertOk()->assertInertia(fn ($response) => $response->has('websiteNavigation', 2));
    }

    public function test_main_page_drafts_preserve_existing_public_content(): void
    {
        $school = School::factory()->create();
        $this->page($school, 'about', ['status' => 'draft', 'is_published' => false, 'content' => 'PRIVATE DRAFT']);
        $this->get('/about')->assertOk()->assertDontSee('PRIVATE DRAFT')->assertInertia(fn ($response) => $response->component('public/about'));
    }

    public function test_private_preview_requires_permission_and_is_not_cacheable_or_indexable(): void
    {
        $school = School::factory()->create();
        $page = $this->page($school, 'private', ['status' => 'draft', 'is_published' => false]);
        $this->get('/content/pages/'.$page->id.'/preview')->assertRedirect('/login');
        $this->actingAsSchoolUser($school);
        $this->get('/content/pages/'.$page->id.'/preview')->assertForbidden();
        $this->actingAsSchoolUser($school, ['manage-content']);
        $response = $this->get('/content/pages/'.$page->id.'/preview')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex,nofollow')
            ->assertInertia(fn ($response) => $response->where('preview', true)->where('editorUrl', '/content/pages/'.$page->id.'/edit'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_public_pages_navigation_and_preview_are_scoped_to_the_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $this->page($school, 'school-life', ['show_in_navigation' => true]);
        $other = $this->page($otherSchool, 'foreign', ['show_in_navigation' => true]);
        $this->get('/pages/foreign')->assertNotFound();
        $this->get('/about')->assertInertia(fn ($response) => $response->has('websiteNavigation', 1)->where('websiteNavigation.0.url', '/pages/school-life'));
        $this->actingAsSchoolUser($school, ['manage-content']);
        $this->get('/content/pages/'.$other->id.'/preview')->assertNotFound();
        $this->get('/content/pages/'.$other->id.'/edit')->assertNotFound();
        $this->put('/content/pages/'.$other->id, $this->payload())->assertNotFound();
    }

    public function test_invalid_links_duplicate_slugs_and_unknown_section_types_are_rejected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-content']);
        $this->page($school, 'school-life');
        $this->post('/content/pages', $this->payload())->assertSessionHasErrors('slug');
        foreach (['javascript:alert(1)', '//outside.example', '/\\outside.example', "https://example.com/a\nscript", 'https://'] as $url) {
            $this->post('/content/pages', $this->payload(['slug' => 'new-page', 'sections' => [
                ['type' => 'cta', 'enabled' => true, 'content' => ['button_url' => $url], 'settings' => []],
            ]]))->assertSessionHasErrors('sections.0.content.button_url');
        }
        $this->post('/content/pages', $this->payload(['slug' => 'new-page', 'sections' => [
            ['type' => 'script', 'enabled' => true, 'content' => [], 'settings' => []],
        ]]))->assertSessionHasErrors('sections.0.type');
        $this->post('/content/pages', $this->payload(['slug' => 'new-page', 'status' => 'scheduled']))->assertSessionHasErrors('scheduled_at');
    }

    public function test_public_collections_include_only_publishable_content_and_safe_staff_fields(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $this->page($school, 'updates', ['sections' => [
            ['type' => 'news', 'enabled' => true, 'content' => [], 'settings' => []],
            ['type' => 'staff', 'enabled' => true, 'content' => [], 'settings' => []],
        ]]);
        foreach ([[$school, true, now()], [$school, false, now()], [$school, true, now()->addDay()], [$other, true, now()]] as $index => [$owner, $published, $date]) {
            News::create(['school_id' => $owner->id, 'title' => 'News '.$index, 'slug' => 'news-'.$index,
                'content' => 'Story', 'is_published' => $published, 'published_at' => $date]);
        }
        foreach ([true, false] as $featured) {
            StaffProfile::create(['school_id' => $school->id, 'first_name' => 'Teacher', 'last_name' => $featured ? 'Public' : 'Private', 'is_featured' => $featured]);
        }
        $this->get('/pages/updates')->assertOk()->assertInertia(fn ($response) => $response
            ->has('collections.news', 1)->where('collections.news.0.title', 'News 0')
            ->has('collections.staff', 1)->where('collections.staff.0.title', 'Teacher Public')
            ->missing('collections.staff.0.user_id')->missing('collections.news.0.content'));
    }
}
