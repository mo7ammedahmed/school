<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Localization\Models\InterfaceTranslation;
use App\Domain\Localization\Services\InterfaceCatalog;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The dictionary travels with the page.
 *
 * Arabic screens used to buy their words one provider call at a time while the
 * reader waited, which is what made them slow and left the strings nobody had
 * reached in English. The dictionary is now handed to the browser with the page,
 * so the known Arabic is painted before the first frame and the provider is only
 * ever asked about what is genuinely new.
 */
class InterfaceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_arabic_page_is_handed_the_dictionary(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['view-dashboard']);
        $this->withSession(['locale' => 'ar']);
        $this->remember('Total Students', 'إجمالي الطلاب');

        $this->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page
            ->where('uiCopy.translations', ['Total Students' => 'إجمالي الطلاب'])
            ->where('uiCopy.version', InterfaceTranslation::catalogVersion())
        );
    }

    public function test_an_english_page_is_not_handed_it(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['view-dashboard']);
        $this->withSession(['locale' => 'en']);
        $this->remember('Total Students', 'إجمالي الطلاب');

        $this->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page->where('uiCopy', null));
    }

    public function test_a_visitor_is_not_handed_it(): void
    {
        School::factory()->create();
        $this->withSession(['locale' => 'ar']);
        $this->remember('Total Students', 'إجمالي الطلاب');

        // The public website renders copy that is already translated at the
        // source, so a guest has no use for the dashboard's dictionary.
        $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->where('uiCopy', null));
    }

    public function test_the_dictionary_is_not_sent_again_once_the_browser_holds_it(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['view-dashboard']);
        $this->withSession(['locale' => 'ar']);
        $this->remember('Total Students', 'إجمالي الطلاب');

        $first = $this->get('/dashboard')->assertOk();

        $cookie = $first->getCookie(InterfaceCatalog::COOKIE);

        $this->assertNotNull($cookie, 'The version cookie is what keeps the payload off later navigations.');

        $this->withCookie(InterfaceCatalog::COOKIE, $cookie->getValue())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('uiCopy', null));
    }

    public function test_an_edited_dictionary_is_sent_again(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['view-dashboard']);
        $this->withSession(['locale' => 'ar']);
        $this->remember('Total Students', 'إجمالي الطلاب');

        $stale = InterfaceTranslation::catalogVersion();

        $this->remember('Attendance Rate', 'نسبة الحضور');

        $this->assertNotSame($stale, InterfaceTranslation::catalogVersion());

        $this->withCookie(InterfaceCatalog::COOKIE, $stale)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('uiCopy.translations.Attendance Rate', 'نسبة الحضور'));
    }

    public function test_the_catalog_endpoint_serves_the_whole_dictionary_for_free(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['view-dashboard']);
        TranslationSettings::for($school)->save(['auto_translate' => true]);
        config(['services.nvidia.api_key' => 'nvapi-deployment-key']);

        $this->remember('Total Students', 'إجمالي الطلاب');
        $this->remember('Attendance Rate', 'نسبة الحضور');

        Http::fake();

        $this->getJson('/ui/copy/catalog')
            ->assertOk()
            ->assertJsonPath('translations.Total Students', 'إجمالي الطلاب')
            ->assertJsonPath('translations.Attendance Rate', 'نسبة الحضور')
            ->assertJsonPath('version', InterfaceTranslation::catalogVersion());

        // The point of the endpoint: it reads the catalog and nothing else.
        Http::assertNothingSent();
    }

    public function test_the_catalog_endpoint_requires_authentication(): void
    {
        $this->getJson('/ui/copy/catalog')->assertUnauthorized();
    }

    private function remember(string $english, string $arabic): void
    {
        InterfaceTranslation::query()->create([
            'source_hash' => InterfaceTranslation::hashSource($english),
            'english' => $english,
            'arabic' => $arabic,
        ]);
    }
}
