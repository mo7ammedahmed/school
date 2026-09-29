<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_changes_are_saved_and_audited(): void
    {
        Permission::create(['name' => 'manage-settings', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage-settings');
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['school_id' => $school->id])
            ->post('/settings/appearance', [
                'theme' => 'system',
                'primary_color' => '#0a5c42',
                'secondary_color' => '#f2efe8',
                'accent_color' => '#efecdf',
            ]);

        $response->assertRedirect('/settings/appearance');
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'primary_color' => '#0a5c42',
            'secondary_color' => '#f2efe8',
            'accent_color' => '#efecdf',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $school->id,
            'description' => 'appearance_updated',
        ]);
    }

    /**
     * The screen is bilingual, so the confirmation the operator reads after a
     * save is too: an Arabic session gets an Arabic message.
     */
    public function test_the_saved_confirmation_follows_the_locale(): void
    {
        Permission::create(['name' => 'manage-settings', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage-settings');
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['school_id' => $school->id, 'locale' => 'ar'])
            ->post('/settings/appearance', [
                'theme' => 'system',
                'primary_color' => '#0a5c42',
                'secondary_color' => '#f2efe8',
                'accent_color' => '#efecdf',
            ])
            ->assertSessionHas('success', 'تم تحديث إعدادات المظهر والسمة بنجاح.');
    }

    /**
     * The screen offers a few choices and derives fifty-odd colours, so the
     * payload it is given has to carry the tokens those choices replace. A
     * trimmed payload would leave the presets with nothing to write into, and
     * the screen would look fine while doing nothing.
     */
    public function test_the_screen_is_given_the_tokens_its_choices_replace(): void
    {
        $user = $this->settingsUser();
        $school = $user->memberships()->first()->school;
        $school->getThemeConfig();

        $this->get('/settings/appearance')->assertOk()->assertInertia(fn ($page) => $page
            ->component('settings/appearance')
            ->where('themeConfig.colorPrimary', '#0a5c42')
            ->has('themeConfig.fontSans')
            ->has('themeConfig.radiusSm')
            ->has('themeConfig.shadowSm')
            ->has('themeDefaults.fontSans')
        );
    }

    /**
     * A font pairing, a corner style and an elevation are stored as the plain
     * tokens the design system already reads, so picking one is a save like any
     * other — no second mechanism to keep in step.
     */
    public function test_a_named_typography_choice_is_stored_and_read_back(): void
    {
        $user = $this->settingsUser();
        $school = $user->memberships()->first()->school;

        $systemSans = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "IBM Plex Sans Arabic", "Noto Sans Arabic", sans-serif';

        $this->actingAs($user)
            ->withSession(['school_id' => $school->id])
            ->post('/settings/appearance', [
                'theme' => 'system',
                // The screen always sends the brand colours alongside the
                // palette, because the seeds are what the rest is derived from.
                'primary_color' => '#0a5c42',
                'secondary_color' => '#f2efe8',
                'accent_color' => '#efecdf',
                'theme_config' => json_encode([
                    'colorPrimary' => '#0a5c42',
                    'fontSans' => $systemSans,
                    'radiusSm' => '0rem',
                    'shadowMd' => '0 1px 2px 0 rgb(28 26 22 / 0.05)',
                ], JSON_THROW_ON_ERROR),
            ])
            ->assertRedirect('/settings/appearance');

        $saved = $school->fresh()->getThemeConfig();

        $this->assertSame($systemSans, $saved['fontSans']);
        $this->assertSame('0rem', $saved['radiusSm']);
        $this->assertSame('0 1px 2px 0 rgb(28 26 22 / 0.05)', $saved['shadowMd']);

        // And the screen that offered the choice reads them back.
        $this->get('/settings/appearance')->assertOk()->assertInertia(fn ($page) => $page
            ->where('themeConfig.fontSans', $systemSans)
            ->where('themeConfig.radiusSm', '0rem')
        );
    }

    private function settingsUser(): User
    {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage-settings');

        $school = School::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }
}
