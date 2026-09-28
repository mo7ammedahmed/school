<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The dashboard's own interface words.
 *
 * Choosing Arabic used to translate the content and leave the chrome in
 * English. The browser now posts the visible interface strings here and the
 * school's own provider answers, once per string, so the fix covers every
 * screen rather than only the ones that were re-typed by hand.
 */
class UiCopyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A deployment key is present unless a test says otherwise.
        config(['services.nvidia.api_key' => 'nvapi-deployment-key']);
    }

    public function test_interface_words_come_back_translated(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('الترجمات');

        $this->postJson('/ui/copy', ['strings' => ['Translations']])
            ->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonPath('translations.Translations', 'الترجمات');
    }

    public function test_a_string_is_only_paid_for_once(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('الترجمات');

        $this->postJson('/ui/copy', ['strings' => ['Translations']])->assertOk();
        $this->postJson('/ui/copy', ['strings' => ['Translations']])->assertOk();

        // The provider is asked once; the second visit is served from the cache.
        Http::assertSentCount(1);
    }

    public function test_duplicates_and_blank_strings_cost_nothing_extra(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('الترجمات');

        $this->postJson('/ui/copy', ['strings' => ['Translations', 'Translations', '', '   ']])
            ->assertOk()
            ->assertJsonCount(1, 'translations');

        Http::assertSentCount(1);
    }

    public function test_help_text_longer_than_a_label_is_still_copy(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('ترجمة');

        $sentence = 'Checks every Arabic and English field in the system and translates the empty side, '
            .'so older records catch up with the ones created since. Runs in small batches, so you can '
            .'watch the progress and stop at any time.';

        // The point of the case is a string no label-length cap would allow.
        $this->assertGreaterThan(200, strlen($sentence));

        $this->postJson('/ui/copy', ['strings' => [$sentence]])
            ->assertOk()
            ->assertJsonCount(1, 'translations');
    }

    public function test_a_page_with_nothing_to_translate_is_not_an_error(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->postJson('/ui/copy', ['strings' => []])
            ->assertOk()
            ->assertJsonPath('translations', []);

        Http::assertNothingSent();
    }

    public function test_a_school_without_a_provider_keeps_its_english_chrome(): void
    {
        config(['services.nvidia.api_key' => null]);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->postJson('/ui/copy', ['strings' => ['Translations']])
            ->assertOk()
            ->assertJsonPath('configured', false)
            ->assertJsonPath('translations', []);

        Http::assertNothingSent();
    }

    public function test_turning_automatic_translation_off_stops_the_interface_pass(): void
    {
        $school = School::factory()->create();
        TranslationSettings::for($school)->save(['auto_translate' => false]);
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->postJson('/ui/copy', ['strings' => ['Translations']])
            ->assertOk()
            ->assertJsonPath('configured', false);

        Http::assertNothingSent();
    }

    public function test_a_provider_failure_leaves_the_words_readable(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        $this->postJson('/ui/copy', ['strings' => ['Translations']])
            ->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonPath('translations', []);
    }

    public function test_the_endpoint_requires_authentication(): void
    {
        $this->postJson('/ui/copy', ['strings' => ['Translations']])->assertUnauthorized();
    }

    public function test_a_batch_is_capped_so_one_page_cannot_flood_the_provider(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->postJson('/ui/copy', ['strings' => array_map('strval', range(1, 201))])
            ->assertStatus(422)
            ->assertJsonValidationErrors('strings');

        Http::assertNothingSent();
    }

    public function test_repeated_batches_are_rate_limited(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('الترجمات');

        for ($i = 0; $i < 40; $i++) {
            $this->postJson('/ui/copy', ['strings' => ['Translations']])->assertOk();
        }

        $this->postJson('/ui/copy', ['strings' => ['Translations']])
            ->assertStatus(429)
            // The strings come back as pending so the next pass picks them up.
            ->assertJsonPath('pending', ['Translations']);
    }

    private function fakeTranslation(string $translated): void
    {
        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [['message' => ['content' => $translated]]],
            ]),
        ]);
    }

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
