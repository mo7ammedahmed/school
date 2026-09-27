<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The document head is what a search engine, a link preview and an assistant
 * read, so the school's own identity and the page's own name have to be in the
 * first HTML response — not added later by JavaScript.
 */
class SiteMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_site_names_the_school_instead_of_the_app(): void
    {
        School::factory()->create(['name_en' => 'Al Noor School', 'name_ar' => 'مدرسة النور']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title', false)
            ->assertSee('Al Noor School', false)
            ->assertSee('og:site_name', false)
            ->assertSee('EducationalOrganization', false);
    }

    public function test_the_description_follows_the_visitor_language(): void
    {
        School::factory()->create([
            'name_en' => 'Al Noor School',
            'name_ar' => 'مدرسة النور',
            'description_en' => 'A bilingual school in Riyadh.',
            'description_ar' => 'مدرسة ثنائية اللغة في الرياض.',
        ]);

        $this->get('/')->assertSee('A bilingual school in Riyadh.', false);

        $this->withSession(['locale' => 'ar'])
            ->get('/')
            ->assertSee('مدرسة ثنائية اللغة في الرياض.', false);
    }

    public function test_a_public_page_is_named_after_its_route(): void
    {
        School::factory()->create(['name_en' => 'Al Noor School']);

        $this->get('/admissions')
            ->assertOk()
            ->assertSee('Admissions', false);

        // The Arabic name of the same page, when the site is in Arabic.
        $this->withSession(['locale' => 'ar'])
            ->get('/about')
            ->assertSee('من نحن', false);
    }

    public function test_a_signed_in_page_is_named_after_its_route_and_kept_out_of_search(): void
    {
        $school = School::factory()->create(['name_en' => 'Al Noor School']);
        $this->actingAsMember($school, ['manage-settings']);

        $this->get('/settings/notifications-config')
            ->assertOk()
            ->assertSee('Notifications Config', false)
            ->assertSee('noindex', false);
    }

    public function test_the_appearance_screen_is_named_in_both_languages(): void
    {
        $school = School::factory()->create(['name_en' => 'Al Noor School', 'name_ar' => 'مدرسة النور']);
        $this->actingAsMember($school, ['manage-settings']);

        // An ampersand is escaped inside the title, hence the entity here.
        $this->get('/settings/appearance')
            ->assertOk()
            ->assertSee('Appearance &amp; Theme', false);

        $this->withSession(['locale' => 'ar'])
            ->get('/settings/appearance')
            ->assertOk()
            ->assertSee('المظهر والسمة', false);
    }

    public function test_an_unknown_url_gets_a_titled_error_page_that_is_not_indexed(): void
    {
        School::factory()->create();

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page Not Found', false)
            ->assertSee('noindex', false);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsMember(School $school, array $permissions = []): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
