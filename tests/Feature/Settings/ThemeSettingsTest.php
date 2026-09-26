<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_school_seeds_its_accent_from_the_brand_colour(): void
    {
        $school = School::factory()->create(['primary_color' => '#123456']);

        $modes = $school->getThemeModes();

        $this->assertSame('#123456', $modes['light']['accent']);
        $this->assertSame('#123456', $modes['dark']['accent']);
        $this->assertSame('#ffffff', $modes['light']['surface']);
        $this->assertSame('#070707', $modes['dark']['background']);
    }

    public function test_saving_palettes_persists_both_modes_and_syncs_the_primary_colour(): void
    {
        $school = School::factory()->create();

        $school->setThemeModes([
            'light' => ['accent' => '#aa0000', 'background' => '#fafafa'],
            'dark' => ['accent' => '#00aa00', 'background' => '#010101'],
        ]);

        $modes = $school->fresh()->getThemeModes();

        $this->assertSame('#aa0000', $modes['light']['accent']);
        $this->assertSame('#fafafa', $modes['light']['background']);
        $this->assertSame('#00aa00', $modes['dark']['accent']);
        $this->assertSame('#010101', $modes['dark']['background']);

        // Untouched tokens keep their defaults rather than disappearing.
        $this->assertSame('#ffffff', $modes['light']['surface']);

        // The brand column follows the light accent so exports stay in step.
        $this->assertSame('#aa0000', $school->fresh()->primary_color);
    }

    public function test_a_user_can_persist_their_colour_mode_preference(): void
    {
        $school = School::factory()->create();
        $user = $this->actingAsSchoolUser($school);

        $this->post('/settings/theme/mode', ['mode' => 'dark'])->assertRedirect();

        $this->assertSame('dark', $user->fresh()->theme);
    }

    public function test_the_theme_editor_renders_both_palettes(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $response = $this->get('/settings/theme');
        $response->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertArrayHasKey('light', $props['themeModes']);
        $this->assertArrayHasKey('dark', $props['themeModes']);
        $this->assertSame(
            ['accent', 'background', 'surface', 'text', 'muted'],
            array_keys($props['themeModes']['light'])
        );
    }

    public function test_an_invalid_colour_is_rejected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->post('/settings/theme', [
            'light' => [
                'accent' => 'not-a-colour',
                'background' => '#ffffff',
                'surface' => '#ffffff',
                'text' => '#000000',
                'muted' => '#888888',
            ],
            'dark' => [
                'accent' => '#006c55',
                'background' => '#070707',
                'surface' => '#0b0b0b',
                'text' => '#f4f4f1',
                'muted' => '#a4a4a8',
            ],
        ])->assertSessionHasErrors('light.accent');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsSchoolUser(School $school, array $permissions = []): User
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
