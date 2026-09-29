<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Localization\Models\InterfaceTranslation;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Filling the dictionary before anyone is waiting on a page.
 *
 * The browser can only ask about strings it has reached, so screens nobody has
 * opened stayed English. The prefill command buys the whole list offline; a
 * second run pays only for what the app has gained since.
 */
class PrefillInterfaceTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private string $manifest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifest = storage_path('app/testing-interface-strings-'.uniqid().'.json');
        config(['services.nvidia.api_key' => 'nvapi-deployment-key']);
    }

    protected function tearDown(): void
    {
        File::delete($this->manifest);

        parent::tearDown();
    }

    public function test_a_dry_run_reports_the_backlog_without_talking_to_the_provider(): void
    {
        School::factory()->create();
        $this->writeManifest(['Total Students', 'Attendance Rate']);
        Http::fake();

        $this->artisan('translations:prefill', ['--manifest' => $this->manifest, '--dry-run' => true])
            ->expectsOutputToContain('2 to go')
            ->assertSuccessful();

        Http::assertNothingSent();

        $this->assertDatabaseCount('interface_translations', 0);
    }

    public function test_a_run_fills_the_dictionary(): void
    {
        School::factory()->create();
        $this->writeManifest(['Total Students', 'Attendance Rate']);
        $this->fakeTranslation('ترجمة');

        $empty = InterfaceTranslation::catalogVersion();

        $this->artisan('translations:prefill', ['--manifest' => $this->manifest])
            ->expectsOutputToContain('Added 2 translations')
            ->assertSuccessful();

        $this->assertDatabaseHas('interface_translations', [
            'source_hash' => InterfaceTranslation::hashSource('Total Students'),
            'english' => 'Total Students',
            'arabic' => 'ترجمة',
            'updated_by' => null,
        ]);

        $this->assertDatabaseCount('interface_translations', 2);

        // The catalog moved, so browsers holding the old one are sent the new.
        $this->assertNotSame($empty, InterfaceTranslation::catalogVersion());
    }

    public function test_a_second_run_only_pays_for_what_is_missing(): void
    {
        School::factory()->create();
        $this->writeManifest(['Total Students', 'Attendance Rate']);

        InterfaceTranslation::query()->create([
            'source_hash' => InterfaceTranslation::hashSource('Total Students'),
            'english' => 'Total Students',
            'arabic' => 'إجمالي الطلاب',
        ]);

        $this->fakeTranslation('نسبة الحضور');

        $this->artisan('translations:prefill', ['--manifest' => $this->manifest])
            ->expectsOutputToContain('Added 1 translations')
            ->assertSuccessful();

        // One provider call for the one string that was actually missing.
        Http::assertSentCount(1);

        $this->assertDatabaseHas('interface_translations', [
            'english' => 'Total Students',
            'arabic' => 'إجمالي الطلاب',
        ]);
    }

    public function test_a_missing_manifest_is_reported_with_the_command_that_builds_it(): void
    {
        School::factory()->create();

        $this->artisan('translations:prefill', ['--manifest' => $this->manifest])
            ->expectsOutputToContain('node scripts/extract-interface-strings.mjs')
            ->assertFailed();
    }

    public function test_a_school_without_a_provider_is_told_so_instead_of_failing_silently(): void
    {
        config(['services.nvidia.api_key' => null]);

        $school = School::factory()->create();
        TranslationSettings::for($school)->save(['auto_translate' => true]);
        $this->writeManifest(['Total Students']);
        Http::fake();

        $this->artisan('translations:prefill', ['--manifest' => $this->manifest])
            ->expectsOutputToContain('no automatic translation configured')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_an_empty_manifest_is_not_an_error(): void
    {
        School::factory()->create();
        $this->writeManifest([]);

        $this->artisan('translations:prefill', ['--manifest' => $this->manifest])
            ->expectsOutputToContain('nothing to translate')
            ->assertSuccessful();
    }

    /**
     * @param  list<string>  $strings
     */
    private function writeManifest(array $strings): void
    {
        File::put($this->manifest, json_encode(['strings' => $strings], JSON_THROW_ON_ERROR));
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
