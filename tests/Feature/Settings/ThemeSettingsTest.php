<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // The palettes live on the Appearance screen; /settings/theme forwards.
        $this->get('/settings/theme')->assertRedirect(route('settings.appearance'));

        $response = $this->get('/settings/appearance');
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

        $this->post('/settings/appearance', [
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

    public function test_a_token_can_be_saved_as_a_gradient_instead_of_a_colour(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $gradient = json_encode([
            'type' => 'linear',
            'angle' => 135,
            'stops' => [
                ['color' => '#0a5c42', 'position' => 0],
                ['color' => '#cda253', 'position' => 100],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->post('/settings/appearance', [
            'primary_color' => '#0a5c42',
            'secondary_color' => '#f2efe8',
            'accent_color' => '#efecdf',
            'theme' => 'light',
            'light' => [
                'accent' => '#0a5c42',
                'accent_solid' => '1',
                'accent_gradient' => $gradient,
                'background' => '#f4f3ee',
                'surface' => '#ffffff',
                'text' => '#0a0a0a',
                'muted' => '#6b6b64',
            ],
            'dark' => [
                'accent' => '#0a5c42',
                // A multipart form sends every value as a string.
                'accent_solid' => '0',
                'accent_gradient' => '',
                'background' => '#070707',
                'surface' => '#0b0b0b',
                'text' => '#f4f4f1',
                'muted' => '#a4a4a8',
            ],
        ])->assertRedirect(route('settings.appearance'));

        $modes = $school->fresh()->getThemeModes();

        // The gradient survives the round-trip so the editor can reopen it.
        $this->assertJson($modes['light']['accent_gradient']);
        $this->assertSame(135, json_decode($modes['light']['accent_gradient'], true)['angle']);
        $this->assertSame('#0a5c42', $modes['light']['accent']);

        // The Solid switch is stored too, and the dark pair stays flat.
        $this->assertTrue((bool) $modes['light']['accent_solid']);
        $this->assertEmpty($modes['dark']['accent_gradient']);
    }

    /**
     * @param  list<string>  $permissions
     */
}
