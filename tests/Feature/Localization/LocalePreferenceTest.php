<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Http\Controllers\LocaleController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_choosing_a_locale_persists_it_on_the_server(): void
    {
        $school = School::factory()->create();
        $user = $this->actingAsSchoolUser($school);

        $this->postJson('/locale', ['locale' => 'ar'])
            ->assertOk()
            ->assertJson(['locale' => 'ar']);

        $this->assertSame('ar', session('locale'));
        $this->assertSame('ar', $user->fresh()->locale);
        $this->assertTrue(
            collect(cookie()->getQueuedCookies())->contains(fn ($cookie) => $cookie->getName() === LocaleController::COOKIE),
        );
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->postJson('/locale', ['locale' => 'fr'])->assertStatus(422);
        $this->assertNull(session('locale'));
    }

    public function test_the_server_renders_the_arabic_column_once_arabic_is_selected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name_en' => 'Year One',
            'name_ar' => 'السنة الأولى',
        ]);

        // Before: the server has no locale preference, so the English name wins.
        $this->get('/academic-years')
            ->assertInertia(fn ($page) => $page->where('academicYears.data.0.name', 'Year One'));

        $this->postJson('/locale', ['locale' => 'ar'])->assertOk();

        // After: the same request renders the Arabic column.
        $this->get('/academic-years')
            ->assertInertia(fn ($page) => $page->where('academicYears.data.0.name', 'السنة الأولى'));
    }

    public function test_the_shared_locale_prop_follows_the_session(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->get('/academic-years')
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));

        session(['locale' => 'ar']);

        $this->get('/academic-years')
            ->assertInertia(fn ($page) => $page->where('locale', 'ar'));
    }

    private function actingAsSchoolUser(School $school): User
    {
        $user = User::factory()->create(['locale' => 'en']);

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
