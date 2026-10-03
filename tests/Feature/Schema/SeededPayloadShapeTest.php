<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Communication\Models\Announcement;
use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Learning\Models\QuizAttempt;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A JSON column holds data, not a JSON-encoded string of data.
 *
 * The demo seeders passed `json_encode(...)` into columns the models already
 * cast to `array`. The value was encoded twice, so the model handed the page a
 * string where an array belongs: `quizzes/show` died on "questions.map is not a
 * function", the announcements list on "`.replace` is not a function", and the
 * report-card screens had silently wrong grades. None of it shows up as a 5xx —
 * the PHP side is happy, only the browser breaks — so it is asserted here.
 *
 * The columns whose contract is a scalar inside the JSON column are the same
 * trap in reverse: `announcements.target_audience` is a single label, and
 * seeding it as `['all']` made the list render an array.
 */
class SeededPayloadShapeTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<class-string<Model>> */
    private const JSON_MODELS = [
        Quiz::class,
        QuizAttempt::class,
        ReportCard::class,
        Announcement::class,
        GatewayTransaction::class,
        AuditLog::class,
    ];

    public function test_seeded_json_columns_are_not_encoded_twice(): void
    {
        $this->seed();

        $offenders = [];

        foreach (self::JSON_MODELS as $class) {
            $model = new $class;

            foreach ($model->getCasts() as $column => $cast) {
                if (! in_array($cast, ['array', 'json'], true)) {
                    continue;
                }

                $raw = DB::table($model->getTable())
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->pluck($column);

                foreach ($raw as $value) {
                    $decoded = json_decode((string) $value, true);

                    // The outer value is a string that is itself parsable JSON
                    // text: the seeder encoded an already-encoded value.
                    if (! is_string($decoded) || $decoded === '') {
                        continue;
                    }

                    if (! in_array($decoded[0] ?? '', ['[', '{', '"'], true)) {
                        continue;
                    }

                    json_decode($decoded, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $offenders[] = $model->getTable().'.'.$column.' (id '.$this->idOf($model, $column, $value).')';
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "A JSON column encoded twice reads back as a string, and the page that maps over it dies:\n  ".implode("\n  ", $offenders),
        );
    }

    public function test_seeded_rows_decode_to_the_shapes_the_pages_read(): void
    {
        $this->seed();

        $school = School::query()->firstOrFail();
        $this->app->make(TenantContext::class)->set((int) $school->id);

        $quiz = Quiz::query()->firstOrFail();
        $this->assertIsArray($quiz->questions);
        $this->assertNotEmpty($quiz->questions);

        foreach ($quiz->questions as $question) {
            $this->assertIsArray($question['options'] ?? null, 'A quiz question needs the options list the paper renders.');
        }

        $this->assertIsArray(QuizAttempt::query()->firstOrFail()->answers);

        $reportCard = ReportCard::query()->firstOrFail();
        $this->assertIsArray($reportCard->grades);
        $this->assertIsArray($reportCard->attendance_summary);
        $this->assertIsArray($reportCard->grades[0] ?? null);

        $audience = Announcement::query()->firstOrFail()->target_audience;
        $this->assertIsString($audience);
        $this->assertStringNotContainsString('"', $audience, 'The audience label is rendered with `.replace`, not JSON.');

        $this->assertIsArray(GatewayTransaction::query()->firstOrFail()->response);
        $this->assertIsArray(AuditLog::query()->firstOrFail()->new_values);
    }

    private function idOf(Model $model, string $column, mixed $value): int|string
    {
        return DB::table($model->getTable())
            ->where($column, $value)
            ->value('id') ?? '?';
    }
}
