<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_school_admin_can_create_a_draft_page(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-content']);

        $response = $this->post('/content/pages', [
            'title' => 'Admissions',
            'title_ar' => 'القبول',
            'slug' => 'admissions',
            'content' => 'Apply to our school.',
            'template' => 'standard',
            'sections' => [['type' => 'hero', 'enabled' => true, 'content' => ['title' => 'Welcome'], 'settings' => []]],
            'status' => 'draft',
            'robots' => 'index,follow',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('content_pages', [
            'school_id' => $school->id,
            'slug' => 'admissions',
            'status' => 'draft',
            'is_published' => 0,
        ]);
    }

    public function test_an_arabic_only_page_gets_its_english_title_translated(): void
    {
        config(['services.nvidia.api_key' => 'nvapi-deployment-key']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-content']);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Admissions']]],
            ]),
        ]);

        $this->post('/content/pages', [
            'title' => '',
            'title_ar' => 'القبول',
            'slug' => 'admissions-ar',
            'template' => 'standard',
            'status' => 'draft',
            'robots' => 'index,follow',
        ])->assertRedirect();

        $page = ContentPage::where('slug', 'admissions-ar')->firstOrFail();
        $this->assertSame('القبول', $page->title_ar);
        $this->assertSame('Admissions', $page->title);

        // The direction sent to the provider is Arabic to English.
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], 'Arabic to English'));
    }

    public function test_a_page_with_neither_title_is_still_rejected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-content']);

        $this->post('/content/pages', [
            'title' => '',
            'title_ar' => '',
            'slug' => 'nameless',
            'template' => 'standard',
            'status' => 'draft',
            'robots' => 'index,follow',
        ])->assertSessionHasErrors('title');
    }

    /**
     * The list screen links here, and both create and update redirect here: the
     * editor is the one screen every content-page write ends on. It shares the
     * `content/pages/create` component, which submits a PUT when it is handed a
     * page, so the name it answers to is pinned here.
     */
    public function test_the_edit_screen_renders_the_page_form(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-content']);

        $page = ContentPage::create([
            'school_id' => $school->id,
            'title' => 'About us',
            'slug' => 'about-us',
            'template' => 'standard',
            'status' => 'draft',
            'is_published' => false,
            'robots' => 'index,follow',
        ]);

        $this->get("/content/pages/{$page->id}/edit")->assertOk()->assertInertia(fn ($inertia) => $inertia
            ->component('content/pages/create')
            ->where('page.id', $page->id)
            ->where('page.slug', 'about-us')
        );
    }

    public function test_published_pages_are_public_but_drafts_are_not(): void
    {
        $school = School::factory()->create();
        ContentPage::create([
            'school_id' => $school->id,
            'title' => 'Published page',
            'slug' => 'published-page',
            'status' => 'published',
            'is_published' => true,
            'published_at' => now(),
            'robots' => 'index,follow',
        ]);
        ContentPage::create([
            'school_id' => $school->id,
            'title' => 'Draft page',
            'slug' => 'draft-page',
            'status' => 'draft',
            'is_published' => false,
            'robots' => 'index,follow',
        ]);

        $this->get('/pages/published-page')->assertOk();
        $this->get('/pages/draft-page')->assertNotFound();
    }
}
