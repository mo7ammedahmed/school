<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_school_admin_can_create_a_draft_page(): void
    {
        Permission::create(['name' => 'manage-content', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage-content');
        $school = School::factory()->create();
        UserMembership::factory()->create(['user_id' => $user->id, 'school_id' => $school->id, 'is_active' => true]);

        $response = $this->actingAs($user)->post('/content/pages', [
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
