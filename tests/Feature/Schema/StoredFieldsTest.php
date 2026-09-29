<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use App\Domain\Content\Models\News;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Guardian;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Two fields the screens have always collected had no column behind them.
 *
 * A guardian's emergency contact is asked for on create, read back on edit and
 * printed on the detail screen. A news article's category is a required choice
 * in the form and is displayed on the admin list, the admin detail screen and
 * both public news screens. Neither was a column, so the operator filled a
 * required field, the save succeeded, and the value was gone — the public site
 * rendering a blank where the category belongs.
 *
 * These drive the real routes, because that is the only place the loss was
 * visible: the model accepted the payload and simply had nowhere to put it.
 */
class StoredFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guardians_emergency_contact_is_stored_and_shown_back(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->post('/guardians', [
            'first_name' => 'Amina',
            'last_name' => 'Saleh',
            'relationship' => 'mother',
            'email' => 'amina@example.test',
            'phone' => '0500000000',
            'emergency_contact' => '0555555555',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $guardian = Guardian::firstOrFail();

        $this->assertSame('0555555555', $guardian->emergency_contact);

        // And the detail screen the value is printed on renders it.
        $this->get("/guardians/{$guardian->id}")->assertOk();
    }

    public function test_a_news_category_survives_the_save(): void
    {
        Permission::firstOrCreate(['name' => 'manage-content', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage-content');

        $school = School::factory()->create();
        UserMembership::factory()->create(['user_id' => $user->id, 'school_id' => $school->id, 'is_active' => true]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $this->post('/content/news', [
            'title' => 'Science fair',
            'content' => 'The fair is on Thursday.',
            'category' => 'academic',
            'publish_date' => '2026-10-01',
            'is_published' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $article = News::firstOrFail();

        $this->assertSame('academic', $article->category);
        $this->assertSame($school->id, $article->school_id);
    }

    public function test_editing_an_article_can_change_its_category(): void
    {
        Permission::firstOrCreate(['name' => 'manage-content', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage-content');

        $school = School::factory()->create();
        UserMembership::factory()->create(['user_id' => $user->id, 'school_id' => $school->id, 'is_active' => true]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $article = News::create([
            'school_id' => $school->id,
            'title' => 'Science fair',
            'slug' => 'science-fair',
            'content' => 'The fair is on Thursday.',
            'category' => 'general',
            'is_published' => true,
        ]);

        $this->put("/content/news/{$article->id}", [
            'title' => 'Science fair',
            'content' => 'The fair is on Thursday.',
            'category' => 'sports',
            'publish_date' => '2026-10-01',
            'is_published' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('sports', $article->fresh()->category);
    }
}
