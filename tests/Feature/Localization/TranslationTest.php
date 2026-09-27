<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Academics\Models\Subject;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A deployment key is present unless a test says otherwise.
        config(['services.nvidia.api_key' => 'nvapi-deployment-key']);
    }

    public function test_the_translate_endpoint_returns_a_translation(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('الرياضيات');

        $response = $this->postJson('/translate', [
            'text' => 'Mathematics',
            'from' => 'en',
            'to' => 'ar',
        ]);

        $response->assertOk()->assertJson(['translation' => 'الرياضيات']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer nvapi-deployment-key')
            && str_contains($request['messages'][1]['content'], 'Mathematics')
            && str_contains($request['messages'][1]['content'], 'English to Arabic')
            && str_contains($request['messages'][0]['content'], 'Arabic'));
    }

    public function test_the_translate_endpoint_requires_authentication(): void
    {
        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertUnauthorized();
    }

    public function test_the_translate_endpoint_explains_when_no_key_is_configured(): void
    {
        config(['services.nvidia.api_key' => null]);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No NVIDIA NIM API key is configured for translation.');

        Http::assertNothingSent();
    }

    public function test_a_model_failure_is_reported_rather_than_thrown(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The translation service could not be reached: quota exceeded');
    }

    public function test_identifier_text_is_returned_unchanged_without_calling_the_provider(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->postJson('/translate', ['text' => 'Grade 1', 'from' => 'en', 'to' => 'en'])
            ->assertOk()
            ->assertJson(['translation' => 'Grade 1']);

        Http::assertNothingSent();
    }

    public function test_saving_with_only_english_fills_the_arabic_column(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        $this->fakeTranslation('الصف الأول');

        $this->post('/grade-levels', [
            'name_en' => 'Grade One',
            'name_ar' => '',
            'level' => 1,
            'description' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('grade_levels', [
            'name_en' => 'Grade One',
            'name_ar' => 'الصف الأول',
        ]);
    }

    public function test_saving_with_only_arabic_fills_the_english_column(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Grade One']]],
            ]),
        ]);

        $this->post('/grade-levels', [
            'name_en' => '',
            'name_ar' => 'الصف الأول',
            'level' => 1,
            'description' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('grade_levels', [
            'name_ar' => 'الصف الأول',
            'name_en' => 'Grade One',
        ]);
    }

    public function test_a_translation_the_operator_typed_is_never_overwritten(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->post('/grade-levels', [
            'name_en' => 'Grade One',
            'name_ar' => 'الصف الأول',
            'level' => 1,
            'description' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('grade_levels', [
            'name_ar' => 'الصف الأول',
            'name_en' => 'Grade One',
        ]);

        Http::assertNothingSent();
    }

    public function test_saving_with_no_name_in_either_language_is_rejected(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake();

        $this->post('/grade-levels', [
            'name_en' => '',
            'name_ar' => '',
            'level' => 1,
        ])->assertSessionHasErrors('name_en');

        $this->assertDatabaseCount('grade_levels', 0);
    }

    public function test_automatic_translation_can_be_switched_off(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        TranslationSettings::for($school->id)->save(['auto_translate' => false]);

        Http::fake();

        $this->post('/grade-levels', [
            'name_en' => 'Grade One',
            'name_ar' => '',
            'level' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('grade_levels', [
            'name_en' => 'Grade One',
            'name_ar' => null,
        ]);

        Http::assertNothingSent();
    }

    public function test_a_provider_outage_does_not_block_the_save(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response('upstream unavailable', 503),
        ]);

        $this->post('/grade-levels', [
            'name_en' => 'Grade One',
            'name_ar' => '',
            'level' => 1,
        ])->assertRedirect();

        // The record is saved with the language the operator supplied.
        $this->assertDatabaseHas('grade_levels', [
            'name_en' => 'Grade One',
            'name_ar' => null,
        ]);
    }

    public function test_a_school_key_is_encrypted_and_takes_precedence_over_the_deployment_key(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->post('/settings/translations', [
            'provider' => 'nvidia',
            'model' => 'meta/llama-3.1-8b-instruct',
            'auto_translate' => true,
            'source_locale' => 'en',
            'target_locale' => 'ar',
            'api_key' => 'nvapi-school-owned',
        ])->assertRedirect();

        $raw = (string) SchoolSetting::where('school_id', $school->id)
            ->where('key', TranslationSettings::KEY)
            ->value('value');

        $this->assertStringNotContainsString('nvapi-school-owned', $raw, 'the key must be encrypted at rest');

        $settings = TranslationSettings::for($school->id);
        $this->assertSame('nvapi-school-owned', $settings->apiKey());
        $this->assertSame('meta/llama-3.1-8b-instruct', $settings->model());
        $this->assertTrue($settings->masked()['has_api_key']);
        $this->assertArrayNotHasKey('api_key', $settings->masked());

        $this->fakeTranslation('الرياضيات');

        $this->postJson('/translate', ['text' => 'Maths', 'from' => 'en', 'to' => 'ar'])->assertOk();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer nvapi-school-owned'));
    }

    public function test_the_translation_settings_page_loads_for_a_school_admin(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->get('/settings/translations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/translations')
                ->where('settings.provider', 'nvidia')
                ->where('settings.auto_translate', true)
                ->where('settings.configured', true)
                ->where('settings.key_source', 'environment')
                ->has('providers', 5)
                ->has('locales')
            );
    }

    public function test_openai_provider_uses_the_chat_completions_api(): void
    {
        config(['services.openai.api_key' => 'sk-deployment']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        TranslationSettings::for($school->id)->save(['provider' => 'openai', 'model' => 'gpt-4o-mini']);

        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'مرحبا']]]]),
        ]);

        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertOk()
            ->assertJson(['translation' => 'مرحبا']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com/v1/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer sk-deployment')
            && $request['model'] === 'gpt-4o-mini'
            && str_contains($request['messages'][1]['content'], 'Hello'));
    }

    public function test_the_openai_dialect_restates_the_direction_in_the_user_turn(): void
    {
        config(['services.nvidia.api_key' => 'nvapi-deployment-key']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response(['choices' => [['message' => ['content' => 'مدرسة']]]]),
        ]);

        $this->postJson('/translate', ['text' => 'School', 'from' => 'en', 'to' => 'ar'])->assertOk();

        // Translation-specialist models ignore the system turn, so the user
        // turn has to carry the source and target languages explicitly.
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], 'English: School')
            && str_contains($request['messages'][1]['content'], 'Arabic:'));
    }

    public function test_anthropic_provider_uses_the_messages_api(): void
    {
        config(['services.anthropic.api_key' => 'sk-ant-deployment']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        TranslationSettings::for($school->id)->save(['provider' => 'anthropic', 'model' => 'claude-3-5-haiku-latest']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'مرحبا'],
                ],
            ]),
        ]);

        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertOk()
            ->assertJson(['translation' => 'مرحبا']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.anthropic.com/v1/messages')
            && $request->hasHeader('x-api-key', 'sk-ant-deployment')
            && $request->hasHeader('anthropic-version')
            && isset($request['system'])
            && $request['messages'][0]['content'] === 'Hello'
            && $request['max_tokens'] === 1024);
    }

    public function test_gemini_provider_uses_generate_content(): void
    {
        config(['services.gemini.api_key' => 'AIza-deployment']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);
        TranslationSettings::for($school->id)->save(['provider' => 'gemini', 'model' => 'gemini-2.5-flash']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'مرحبا']]]],
                ],
            ]),
        ]);

        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertOk()
            ->assertJson(['translation' => 'مرحبا']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'gemini-2.5-flash:generateContent')
            && $request->hasHeader('x-goog-api-key', 'AIza-deployment')
            && $request['contents'][0]['parts'][0]['text'] === 'Hello'
            && isset($request['systemInstruction']));
    }

    public function test_custom_provider_requires_a_base_url(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->post('/settings/translations', [
            'provider' => 'custom',
            'model' => 'my-model',
            'base_url' => '',
            'auto_translate' => true,
            'source_locale' => 'en',
            'target_locale' => 'ar',
        ])->assertSessionHasErrors('base_url');
    }

    public function test_custom_provider_calls_its_own_endpoint(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->post('/settings/translations', [
            'provider' => 'custom',
            'model' => 'my-model',
            'base_url' => 'https://llm.example.com/v1',
            'auto_translate' => true,
            'source_locale' => 'en',
            'target_locale' => 'ar',
            'api_key' => 'custom-secret',
        ])->assertRedirect();

        Http::fake([
            'llm.example.com/*' => Http::response(['choices' => [['message' => ['content' => 'مرحبا']]]]),
        ]);

        $this->postJson('/translate', ['text' => 'Hello', 'from' => 'en', 'to' => 'ar'])
            ->assertOk()
            ->assertJson(['translation' => 'مرحبا']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'llm.example.com/v1/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer custom-secret'));
    }

    public function test_each_provider_keeps_its_own_encrypted_key(): void
    {
        $school = School::factory()->create();
        $settings = TranslationSettings::for($school->id);

        $settings->save(['provider' => 'nvidia', 'api_key' => 'nvapi-school-owned']);
        $settings->save(['provider' => 'openai', 'api_key' => 'sk-school-owned']);

        $raw = (string) SchoolSetting::where('school_id', $school->id)
            ->where('key', TranslationSettings::KEY)
            ->value('value');
        $this->assertStringNotContainsString('nvapi-school-owned', $raw);
        $this->assertStringNotContainsString('sk-school-owned', $raw);

        TranslationSettings::for($school->id)->save(['provider' => 'nvidia']);
        $this->assertSame('nvapi-school-owned', TranslationSettings::for($school->id)->apiKey());

        TranslationSettings::for($school->id)->save(['provider' => 'openai']);
        $this->assertSame('sk-school-owned', TranslationSettings::for($school->id)->apiKey());
    }

    public function test_the_model_list_is_read_from_the_provider(): void
    {
        config(['services.openai.api_key' => 'sk-deployment']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);
        TranslationSettings::for($school->id)->save(['provider' => 'openai', 'model' => 'gpt-4o-mini']);

        Http::fake([
            'api.openai.com/*' => Http::response(['data' => [['id' => 'gpt-4o-mini'], ['id' => 'gpt-4o']]]),
        ]);

        $this->postJson('/settings/translations/models')
            ->assertOk()
            ->assertJson(['models' => ['gpt-4o', 'gpt-4o-mini']]);
    }

    public function test_the_model_list_reports_a_provider_error(): void
    {
        config(['services.openai.api_key' => 'sk-deployment']);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);
        TranslationSettings::for($school->id)->save(['provider' => 'openai', 'model' => 'gpt-4o-mini']);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'invalid key']], 401),
        ]);

        $this->postJson('/settings/translations/models')
            ->assertStatus(422)
            ->assertJsonPath('message', 'The translation service could not be reached: invalid key');
    }

    public function test_a_user_without_settings_permission_cannot_open_translation_settings(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->get('/settings/translations')->assertForbidden();
    }

    public function test_the_connection_test_reports_the_translated_sample(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->fakeTranslation('مرحبا');

        $this->postJson('/settings/translations/test')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_the_backfill_fills_missing_arabic_from_the_english_name(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH');
        $this->subject($school, 'Science', 'SCI');
        $this->fakeTranslation('الرياضيات');

        $this->postJson('/settings/translations/backfill')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('totals.missing', 2)
            ->assertJsonPath('totals.translated', 2)
            ->assertJsonPath('totals.remaining', 0)
            ->assertJsonFragment([
                'label' => 'Subjects',
                'scanned' => 2,
                'missing' => 2,
                'translated' => 2,
                'failed' => 0,
            ]);

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'name_en' => 'Mathematics',
            'name_ar' => 'الرياضيات',
        ]);

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'name_en' => 'Science',
            'name_ar' => 'الرياضيات',
        ]);
    }

    public function test_the_backfill_fills_a_missing_english_name_from_the_arabic_one(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        Subject::create([
            'school_id' => $school->id,
            'name_ar' => 'الرياضيات',
            'code' => 'MATH',
        ]);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Mathematics']]],
            ]),
        ]);

        $this->postJson('/settings/translations/backfill')
            ->assertOk()
            ->assertJsonPath('totals.translated', 1);

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'name_en' => 'Mathematics',
            'name_ar' => 'الرياضيات',
        ]);

        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], 'Arabic to English'));
    }

    public function test_the_backfill_leaves_a_fully_bilingual_school_alone(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH', 'الرياضيات');

        Http::fake();

        $this->postJson('/settings/translations/backfill')
            ->assertOk()
            ->assertJsonPath('totals.missing', 0)
            ->assertJsonPath('totals.translated', 0);

        Http::assertNothingSent();
    }

    public function test_the_backfill_skips_records_where_both_languages_are_empty(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        Subject::create(['school_id' => $school->id, 'code' => 'BLANK']);

        Http::fake();

        $this->postJson('/settings/translations/backfill')
            ->assertOk()
            ->assertJsonPath('totals.missing', 0);

        Http::assertNothingSent();
    }

    public function test_the_backfill_refuses_to_run_without_a_key_and_changes_nothing(): void
    {
        config(['services.nvidia.api_key' => null]);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);
        $this->subject($school, 'Mathematics', 'MATH');

        Http::fake();

        $this->postJson('/settings/translations/backfill')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath(
                'message',
                'Add an API key for NVIDIA NIM before translating. Nothing was changed.',
            );

        Http::assertNothingSent();

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'name_en' => 'Mathematics',
            'name_ar' => null,
        ]);
    }

    public function test_the_backfill_stops_at_the_configured_budget_and_reports_what_is_left(): void
    {
        config(['bilingual.max_translations_per_run' => 1]);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH');
        $this->subject($school, 'Science', 'SCI');
        $this->subject($school, 'Art', 'ART');
        $this->fakeTranslation('الرياضيات');

        $this->postJson('/settings/translations/backfill')
            ->assertOk()
            ->assertJsonPath('totals.missing', 3)
            ->assertJsonPath('totals.translated', 1)
            ->assertJsonPath('totals.remaining', 2);

        $this->assertSame(
            1,
            Subject::where('school_id', $school->id)->whereNotNull('name_ar')->count(),
        );
    }

    public function test_the_backfill_only_translates_the_current_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();

        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH');
        Subject::create(['school_id' => $otherSchool->id, 'name_en' => 'Foreign', 'code' => 'FRN']);
        $this->fakeTranslation('الرياضيات');

        $this->postJson('/settings/translations/backfill')->assertOk();

        $this->assertNull(
            Subject::where('school_id', $otherSchool->id)->first()?->name_ar,
        );
    }

    public function test_the_backfill_stops_after_repeated_provider_failures(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        foreach (['Mathematics', 'Science', 'Art', 'Music'] as $index => $name) {
            $this->subject($school, $name, 'CODE'.$index);
        }

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        $this->postJson('/settings/translations/backfill')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('totals.translated', 0)
            ->assertJsonPath('totals.failed', 3);

        // The run stops at the failure ceiling instead of retrying all four.
        Http::assertSentCount(3);
    }

    public function test_the_backfill_requires_the_settings_permission(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->postJson('/settings/translations/backfill')->assertForbidden();
    }

    private function subject(School $school, string $english, string $code, ?string $arabic = null): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'name_en' => $english,
            'name_ar' => $arabic,
            'code' => $code,
        ]);
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
