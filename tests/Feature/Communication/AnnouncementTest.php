<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Domain\Communication\Models\Announcement;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The announcement module described a schema that does not exist: it posted
 * `content`, `publish_date`, `expiry_date` and `is_active`, while the table keeps
 * `body`, `start_date`, `end_date` and `is_published`. The model dropped all four
 * on the way in, so an announcement could be created with a title and nothing
 * else, and the screens reading those fields showed blanks. It also never set
 * `school_id`, which is not nullable, so the insert threw outright.
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_announcement_is_created_with_its_body_and_dates(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->post('/announcements', [
            'title' => 'Sports day',
            'title_ar' => 'يوم رياضي',
            'body' => 'The annual sports day is on Thursday.',
            'body_ar' => 'اليوم الرياضي السنوي يوم الخميس.',
            'target_audience' => 'students',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'is_published' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $announcement = Announcement::firstOrFail();

        $this->assertSame($school->id, $announcement->school_id);
        $this->assertSame('The annual sports day is on Thursday.', $announcement->body);
        $this->assertSame('اليوم الرياضي السنوي يوم الخميس.', $announcement->body_ar);
        $this->assertSame('2026-10-01', $announcement->start_date?->toDateString());
        $this->assertTrue((bool) $announcement->is_published);
    }

    public function test_an_announcement_in_one_language_fills_the_other_on_save(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        config(['services.openai.api_key' => 'sk-test']);
        TranslationSettings::for($school->id)->save(['provider' => 'openai', 'model' => 'gpt-4o-mini']);

        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'منحة دراسية متاحة']]]]),
        ]);

        // Arabic only — the English column is the one the forms used to demand.
        $this->post('/announcements', [
            'title' => 'Scholarship available',
            'title_ar' => 'منحة دراسية متاحة',
            'body_ar' => 'التقديم مفتوح حتى نهاية الشهر.',
            'target_audience' => 'parents',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'is_published' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('منحة دراسية متاحة', Announcement::firstOrFail()->body);
    }

    public function test_an_announcement_needs_a_title_in_one_of_the_two_languages(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->post('/announcements', [
            'target_audience' => 'all',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'is_published' => 1,
        ])->assertSessionHasErrors(['title', 'title_ar']);

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_the_list_shows_only_the_active_schools_announcements(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $mine = $this->announcement($school, 'Mine');
        $this->announcement($other, 'Theirs');

        $this->actingAsSchoolUser($school);

        $response = $this->get('/announcements');

        $response->assertOk();

        $rows = $response->viewData('page')['props']['announcements']['data'];

        $this->assertSame([$mine->id], array_column($rows, 'id'));
    }

    public function test_another_schools_announcement_is_not_found_rather_than_shown(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = $this->announcement($other, 'Theirs');

        $this->actingAsSchoolUser($school);

        $this->get("/announcements/{$foreign->id}")->assertNotFound();
        $this->get("/announcements/{$foreign->id}/edit")->assertNotFound();
    }

    public function test_the_detail_screen_renders_both_languages(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $announcement = $this->announcement($school, 'Book fair');

        $response = $this->get("/announcements/{$announcement->id}");

        $response->assertOk();

        $props = $response->viewData('page')['props']['announcement'];

        $this->assertSame('Book fair', $props['title']);
        $this->assertSame('معرض الكتاب', $props['title_ar']);
        $this->assertSame('Weekly', $props['body']);
        $this->assertSame('أسبوعي', $props['body_ar']);
    }

    private function announcement(School $school, string $title): Announcement
    {
        return Announcement::create([
            'school_id' => $school->id,
            'title' => $title,
            'title_ar' => 'معرض الكتاب',
            'body' => 'Weekly',
            'body_ar' => 'أسبوعي',
            'target_audience' => 'all',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'is_published' => true,
        ]);
    }
}
