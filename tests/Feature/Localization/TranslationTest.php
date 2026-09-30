<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Academics\Models\Subject;
use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Models\Event;
use App\Domain\Content\Models\News;
use App\Domain\Localization\Observers\FillsMissingTranslations;
use App\Domain\Localization\Services\TranslationService;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolSetting;
use App\Models\Announcement;
use App\Models\GradeLevel;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_a_translated_field_can_be_saved_without_submitting_the_form(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);
        $gradeLevel = GradeLevel::create([
            'school_id' => $school->id,
            'name_en' => 'Grade One',
            'name_ar' => null,
            'level' => 1,
        ]);

        $this->postJson('/translate/save', [
            'table' => 'grade_levels',
            'id' => $gradeLevel->id,
            'source_column' => 'name_en',
            'source_value' => 'Updated Grade One',
            'column' => 'name_ar',
            'value' => 'الصف الأول',
        ])->assertOk()->assertJson(['saved' => true]);

        $this->assertDatabaseHas('grade_levels', [
            'id' => $gradeLevel->id,
            'name_en' => 'Updated Grade One',
            'name_ar' => 'الصف الأول',
        ]);
    }

    public function test_translated_field_save_rejects_non_bilingual_columns_and_other_schools(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);
        $gradeLevel = GradeLevel::create([
            'school_id' => $otherSchool->id,
            'name_en' => 'Grade One',
            'name_ar' => null,
            'level' => 1,
        ]);

        $this->postJson('/translate/save', [
            'table' => 'grade_levels',
            'id' => $gradeLevel->id,
            'source_column' => 'name_en',
            'source_value' => 'Grade One',
            'column' => 'level',
            'value' => '2',
        ])->assertUnprocessable();

        $this->postJson('/translate/save', [
            'table' => 'grade_levels',
            'id' => $gradeLevel->id,
            'source_column' => 'name_en',
            'source_value' => 'Grade One',
            'column' => 'name_ar',
            'value' => 'الصف الأول',
        ])->assertNotFound();

        $this->assertDatabaseHas('grade_levels', [
            'id' => $gradeLevel->id,
            'name_ar' => null,
            'level' => 1,
        ]);
    }

    public function test_a_school_translation_can_be_saved_by_a_school_settings_manager(): void
    {
        $school = School::factory()->create(['name_en' => 'Old School']);
        $this->actingAsSchoolUser($school, ['manage-schools']);

        $this->postJson('/translate/save', [
            'table' => 'schools',
            'id' => $school->id,
            'source_column' => 'name_en',
            'source_value' => 'Updated School',
            'column' => 'name_ar',
            'value' => 'مدرسة محدثة',
        ])->assertOk();

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name_en' => 'Updated School',
            'name_ar' => 'مدرسة محدثة',
        ]);
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
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);
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
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);

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
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);

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
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);

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
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);

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
        $this->actingAsSchoolUser($school, ['manage-grade-levels']);

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
                // Emptied by this very run, so the report must not still offer it
                // as work left to do.
                'remaining' => 0,
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
            ->assertJsonPath('totals.remaining', 2)
            ->assertJsonFragment(['label' => 'Subjects', 'translated' => 1, 'remaining' => 2]);

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
            'integrate.api.nvidia.com/*' => Http::response([], 429),
        ]);

        $this->postJson('/settings/translations/backfill')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('totals.translated', 0)
            ->assertJsonPath('totals.failed', 1)
            ->assertJsonPath(
                'error',
                'The translation service could not be reached: The translation provider rate limit was reached (HTTP 429). Wait before trying again or check your provider quota.',
            );

        $this->postJson('/settings/translations/backfill')
            ->assertStatus(422)
            ->assertJsonPath('totals.failed', 1);

        // A provider rate limit is not retried for the remaining values.
        Http::assertSentCount(1);
    }

    public function test_the_backfill_requires_the_settings_permission(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->postJson('/settings/translations/backfill')->assertForbidden();
    }

    public function test_a_scan_only_run_reports_the_backlog_without_translating(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH');
        $this->subject($school, 'Science', 'SCI');

        Http::fake();

        $this->postJson('/settings/translations/backfill', ['limit' => 0])
            ->assertOk()
            ->assertJsonPath('totals.missing', 2)
            ->assertJsonPath('totals.translated', 0)
            ->assertJsonPath('totals.remaining', 2)
            ->assertJsonPath('done', false);

        Http::assertNothingSent();

        $this->assertNull(Subject::where('school_id', $school->id)->first()?->name_ar);
    }

    public function test_a_limited_run_translates_one_batch_and_reports_what_is_left(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH');
        $this->subject($school, 'Science', 'SCI');
        $this->subject($school, 'Art', 'ART');
        $this->fakeTranslation('الرياضيات');

        $this->postJson('/settings/translations/backfill', ['limit' => 2])
            ->assertOk()
            ->assertJsonPath('totals.translated', 2)
            ->assertJsonPath('totals.remaining', 1)
            ->assertJsonPath('done', false);

        $this->assertSame(
            2,
            Subject::where('school_id', $school->id)->whereNotNull('name_ar')->count(),
        );

        // Pressing on finishes the job: a second batch has nothing left to do.
        $this->postJson('/settings/translations/backfill', ['limit' => 2])
            ->assertOk()
            ->assertJsonPath('totals.translated', 1)
            ->assertJsonPath('totals.remaining', 0)
            ->assertJsonPath('done', true);
    }

    public function test_a_finished_run_reports_done(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->subject($school, 'Mathematics', 'MATH');
        $this->fakeTranslation('الرياضيات');

        $this->postJson('/settings/translations/backfill', ['limit' => 5])
            ->assertOk()
            ->assertJsonPath('done', true)
            ->assertJsonPath('totals.remaining', 0);
    }

    public function test_the_run_reports_both_translation_directions(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        // One English-only row needs Arabic; one Arabic-only row needs English.
        $this->subject($school, 'Mathematics', 'MATH');
        Subject::create([
            'school_id' => $school->id,
            'name_ar' => 'العلوم',
            'code' => 'SCI',
        ]);

        $this->fakeTranslation('مترجم');

        $this->postJson('/settings/translations/backfill', ['limit' => 5])
            ->assertOk()
            ->assertJsonPath('totals.en_to_ar', 1)
            ->assertJsonPath('totals.ar_to_en', 1)
            ->assertJsonPath('done', true);

        $this->assertSame(
            'مترجم',
            Subject::where('school_id', $school->id)->where('code', 'MATH')->value('name_ar'),
        );
        $this->assertSame(
            'مترجم',
            Subject::where('school_id', $school->id)->where('code', 'SCI')->value('name_en'),
        );
    }

    public function test_a_run_stops_at_its_time_budget_and_reports_what_is_left(): void
    {
        // A slow provider must not carry a run past PHP's execution limit.
        config(['bilingual.max_seconds_per_run' => 2, 'bilingual.request_timeout_seconds' => 1]);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        foreach (['Mathematics', 'Science', 'Art'] as $index => $name) {
            $this->subject($school, $name, 'CODE'.$index);
        }

        Http::fake(function () {
            usleep(500_000);

            return Http::response([
                'choices' => [['message' => ['content' => 'مترجم']]],
            ]);
        });

        $response = $this->postJson('/settings/translations/backfill', ['limit' => 10])
            ->assertOk()
            ->assertJsonPath('done', false);

        $translated = $response->json('totals.translated');
        $remaining = $response->json('totals.remaining');

        $this->assertGreaterThanOrEqual(1, $translated, 'the run should still make progress');
        $this->assertLessThan(3, $translated, 'the run should stop before the clock runs out');
        $this->assertGreaterThanOrEqual(1, $remaining);
        $this->assertSame(3, $translated + $remaining);
    }

    public function test_the_run_never_outlives_phps_own_execution_limit(): void
    {
        // The web server enforces 30s here; the point is that the run reads the
        // limit it is actually given rather than the one assumed at design time.
        ini_set('max_execution_time', '4');

        try {
            $school = School::factory()->create();
            $this->actingAsSchoolUser($school, ['manage-settings']);

            foreach (range(1, 10) as $index) {
                $this->subject($school, 'Subject '.$index, 'CODE'.$index);
            }

            Http::fake(function () {
                usleep(500_000);

                return Http::response([
                    'choices' => [['message' => ['content' => 'مترجم']]],
                ]);
            });

            $started = microtime(true);

            $response = $this->postJson('/settings/translations/backfill')->assertOk();

            $elapsed = microtime(true) - $started;

            // Standing in for the fatal that used to kill the request mid-call:
            // the run has to hand back control before PHP takes it away.
            $this->assertLessThan(4, $elapsed, 'the run outlived max_execution_time');
            $this->assertGreaterThanOrEqual(1, $response->json('totals.translated'));
            $this->assertGreaterThan(0, $response->json('totals.remaining'));
        } finally {
            ini_restore('max_execution_time');
        }
    }

    public function test_the_configured_budgets_fit_inside_a_thirty_second_limit(): void
    {
        $limit = 30;
        $worstCase = config('bilingual.max_seconds_per_run') + config('bilingual.request_timeout_seconds');

        $this->assertLessThan(
            $limit,
            $worstCase,
            'A run plus the call it is waiting on must finish before PHP kills the request.',
        );
    }

    public function test_repeated_text_is_translated_once_and_reused(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        // Three sections share one name, which is normal for school content.
        $this->subject($school, 'Grade One', 'G1');
        $this->subject($school, 'Grade One', 'G1B');
        $this->subject($school, 'Grade One', 'G1C');

        $this->fakeTranslation('الصف الأول');

        $this->postJson('/settings/translations/backfill', ['limit' => 5])
            ->assertOk()
            ->assertJsonPath('totals.translated', 3)
            ->assertJsonPath('done', true);

        // One provider call, three filled columns.
        Http::assertSentCount(1);

        $this->assertSame(
            3,
            Subject::where('school_id', $school->id)->where('name_ar', 'الصف الأول')->count(),
        );
    }

    public function test_the_connection_test_is_not_answered_from_the_cache(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $service = app(TranslationService::class);

        // Warm the cache twice: the second call must not hit the provider.
        $this->fakeTranslation('مرحبا');
        $first = $service->translate('Hello', 'en', 'ar', $school->id);
        $second = $service->translate('Hello', 'en', 'ar', $school->id);

        $this->assertSame('مرحبا', $first);
        // The second call is served from the cache, so it returns the same text
        // without a second trip to the provider.
        $this->assertSame($first, $second);
        Http::assertSentCount(1);

        // "Test connection" translates the same sample, so it has to prove the
        // key works rather than replaying the cached answer.
        $this->postJson('/settings/translations/test')
            ->assertOk()
            ->assertJsonPath('ok', true);

        Http::assertSentCount(2);
    }

    public function test_saving_an_announcement_in_english_fills_the_arabic(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        // Switched on only now: the school row itself is bilingual, and filling
        // it would reach a provider before the fake below is in place.
        config(['bilingual.autofill_in_console' => true]);

        // Announcements are one of the tables no controller ever hooked up, so
        // before the observer this row stayed English-only for ever. Created
        // through the alias the pages use, which an observer registered on the
        // parent class would not have seen.
        $this->fakeTranslation('مرحبا');

        $announcement = Announcement::create([
            'school_id' => $school->id,
            'title' => 'Sports day',
            'body' => 'On Thursday.',
        ]);

        $this->assertSame('مرحبا', $announcement->refresh()->title_ar);
        $this->assertSame('مرحبا', $announcement->body_ar);
    }

    public function test_saving_a_record_in_arabic_fills_the_english(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        // Switched on only now: the school row itself is bilingual, and filling
        // it would reach a provider before the fake below is in place.
        config(['bilingual.autofill_in_console' => true]);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Sports day']]],
            ]),
        ]);

        $event = Event::create([
            'school_id' => $school->id,
            'title_ar' => 'يوم الرياضة',
            'start_date' => now()->addWeek()->toDateString(),
        ]);

        $this->assertSame('Sports day', $event->refresh()->title);
    }

    public function test_a_pair_that_was_not_touched_is_left_alone(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        // Switched on only now: the school row itself is bilingual, and filling
        // it would reach a provider before the fake below is in place.
        config(['bilingual.autofill_in_console' => true]);

        // Legacy content, saved before translation was switched on.
        $subject = FillsMissingTranslations::withoutFilling(
            fn () => $this->subject($school, 'Mathematics', 'MATH'),
        );

        Http::fake();

        // Saving for an unrelated reason must not start translating every
        // record the school already has.
        $subject->refresh()->save();

        Http::assertNothingSent();
        $this->assertNull($subject->refresh()->name_ar);
    }

    public function test_the_sweep_translates_deliberately_rather_than_through_the_observer(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        config(['bilingual.autofill_in_console' => true]);

        $subject = $this->legacySubject($school, 'Mathematics', 'MATH');

        $this->fakeTranslation('الرياضيات');

        $this->postJson('/settings/translations/backfill', ['limit' => 5])->assertOk();

        // One value, one provider call: the sweep's own write must not be
        // translated a second time by the observer.
        Http::assertSentCount(1);
        $this->assertSame('الرياضيات', $subject->refresh()->name_ar);
    }

    public function test_an_import_stops_filling_once_the_budget_is_spent(): void
    {
        // The allowance is set before the first save of the request: a save
        // sequence shares one clock, which is the point of the budget, so it is
        // read once rather than per row.
        config([
            'bilingual.autofill_in_console' => true,
            'bilingual.on_save_seconds' => 1,
        ]);

        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        Http::fake(function () {
            usleep(500_000);

            return Http::response([
                'choices' => [['message' => ['content' => 'مترجم']]],
            ]);
        });

        foreach (range(1, 4) as $index) {
            $this->subject($school, 'Bulk '.$index, 'BULK'.$index);
        }

        $filled = Subject::where('school_id', $school->id)->whereNotNull('name_ar')->count();

        // Where it stops is timing, that it stops is not: the rest is left to
        // the sweep, which reports what is still missing.
        $this->assertGreaterThanOrEqual(1, $filled);
        $this->assertLessThan(4, $filled);
    }

    /**
     * The regression: this endpoint authorises whatever bilingual model the
     * request names, and thirteen of the policies it can reach asked for a
     * permission the catalogue never had.
     *
     * `hasPermissionTo('manage-news')` does not return false for a name that is
     * not seeded — it resolves the name and throws `PermissionDoesNotExist`. So
     * the deny branch was unreachable too: a user without the permission got a
     * 500 rather than the 403 they were owed, and a user with it got a 500 as
     * well. Both tables below are ones the policy map made reachable, and both
     * are named in the request body, so both have to come back as an answer
     * rather than an error.
     */
    public function test_saving_a_translated_field_authorises_instead_of_erroring(): void
    {
        $school = School::factory()->create();

        // Without this the two calls below raise There is no permission named —
        // which is the 500 this case exists to rule out.
        $this->seed(PermissionSeeder::class);

        $page = ContentPage::create([
            'school_id' => $school->id,
            'slug' => 'about-us',
            'title' => 'About us',
        ]);

        $news = News::create([
            'school_id' => $school->id,
            'slug' => 'term-dates',
            'title' => 'Term dates',
            'content' => 'The term starts on Sunday.',
        ]);

        $targets = [
            'content_pages' => $page->id,
            'news' => $news->id,
        ];

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'مترجم']]],
            ]),
        ]);

        // The deny branch: a policy that cannot resolve the name must still say
        // no, and saying no is a 403.
        $this->actingAsSchoolUser($school);

        foreach ($targets as $table => $id) {
            $this->postJson('/translate/save', $this->titleField($table, $id))
                ->assertForbidden();
        }

        // The allow branch: the same two targets, with the permission the
        // policy checks. Anything but a 2xx here is the old 500 again.
        $this->actingAsSchoolUser($school, ['manage-content', 'manage-news']);

        foreach ($targets as $table => $id) {
            $response = $this->postJson('/translate/save', $this->titleField($table, $id));

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "POST /translate/save against [{$table}] returned a server error instead of an answer.",
            );
            $response->assertOk()->assertJson(['saved' => true]);
        }

        $this->assertDatabaseHas('content_pages', [
            'id' => $page->id,
            'title' => 'About us',
            'title_ar' => 'من نحن',
        ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'Term dates',
            'title_ar' => 'مواعيد الفصل',
        ]);
    }

    /** The payload the bilingual field editor posts, for a title pair. */
    private function titleField(string $table, int $id): array
    {
        return [
            'table' => $table,
            'id' => $id,
            'source_column' => 'title',
            'source_value' => $table === 'news' ? 'Term dates' : 'About us',
            'column' => 'title_ar',
            'value' => $table === 'news' ? 'مواعيد الفصل' : 'من نحن',
        ];
    }

    /** A row as it would have been saved before translation existed. */
    private function legacySubject(School $school, string $english, string $code): Subject
    {
        return FillsMissingTranslations::withoutFilling(
            fn () => $this->subject($school, $english, $code),
        );
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
}
