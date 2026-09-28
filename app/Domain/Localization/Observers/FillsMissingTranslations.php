<?php

declare(strict_types=1);

namespace App\Domain\Localization\Observers;

use App\Domain\Localization\Services\TranslationService;
use App\Domain\Localization\Support\BilingualTargets;
use App\Domain\Localization\Support\TranslationBudget;
use App\Domain\Localization\Support\TranslationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

/**
 * Fills the empty side of every bilingual pair when a record is saved.
 *
 * Before this, translation had to be wired into each controller in turn, so the
 * pages nobody had got round to (announcements, news, events, documents,
 * assignments, invoices…) stored one language and silently left the other
 * empty — and eight controllers that *had* been wired up called the service
 * directly, which made filling a thing each page did rather than a property of
 * the bilingual schema. Registering one listener for every model in
 * `config('bilingual.targets')` leaves exactly one mechanism: save a record,
 * and whichever side you left blank is filled.
 *
 * What it is allowed to spend, and where it thinks it is running, come from
 * {@see TranslationBudget} and {@see TranslationContext} rather than from
 * `ini_get()`, `session()` and `app()` read inline here.
 */
final class FillsMissingTranslations
{
    /** Re-entrancy depth: the sweep translates on purpose and must not loop. */
    private static int $suppressed = 0;

    private ?TranslationContext $context;

    public function __construct(
        private readonly TranslationService $translations,
        private readonly TranslationBudget $budget,
        ?TranslationContext $context = null,
    ) {
        $this->context = $context;
    }

    /**
     * Listens for every model save and fills the pairs of the tables it knows.
     *
     * Deliberately not `Model::observe()`: the app models are thin subclasses of
     * the domain ones (`App\Models\Announcement` extends the domain announcement)
     * and Eloquent fires an observer on the class it was registered for, so an
     * observer attached to the parent never saw the rows the pages actually
     * write. Keyed by table, the listener covers both spellings — and anything
     * added later.
     *
     * The listener is resolved from the container per save so that one request
     * shares one budget: a page of saves is one clock, not a fresh allowance
     * each time.
     */
    public static function register(): void
    {
        app()->scoped(self::class, fn (): self => new self(
            app(TranslationService::class),
            TranslationBudget::forSave(),
        ));

        Event::listen('eloquent.saving: *', static function (string $event, array $payload): void {
            $model = $payload[0] ?? null;

            if ($model instanceof Model && self::$suppressed === 0) {
                app(self::class)->saving($model);
            }
        });
    }

    /**
     * Runs a write without filling translations, for callers that translate
     * deliberately — the sweep, which counts what it does and budgets it.
     */
    public static function withoutFilling(callable $callback): mixed
    {
        self::$suppressed++;

        try {
            return $callback();
        } finally {
            self::$suppressed--;
        }
    }

    public function saving(Model $model): void
    {
        $context = $this->context ??= TranslationContext::current();

        if (! $context->fillsOnSave()) {
            return;
        }

        $pairs = BilingualTargets::forTable($model->getTable());

        if ($pairs === []) {
            return;
        }

        $school = $context->schoolIdFor($model);

        // Off, or no key yet: an untranslated record is better than a broken save.
        if (! $this->translations->autoTranslateEnabled($school)) {
            return;
        }

        foreach ($pairs as $pair) {
            [$englishKey, $arabicKey] = [$pair['en'], $pair['ar']];

            // Only a pair this save touched can need filling, and a value the
            // operator typed is never overwritten.
            if (! $model->isDirty($englishKey) && ! $model->isDirty($arabicKey)) {
                continue;
            }

            $english = self::text($model->getAttribute($englishKey));
            $arabic = self::text($model->getAttribute($arabicKey));

            if (($english === null) === ($arabic === null)) {
                continue;
            }

            // The budget is consulted per *value*, never mid-value: a value is
            // either translated or left for the sweep. What it will not do is
            // start a call that cannot finish inside this request — an untranslated
            // pair is a much better outcome than the fatal that used to lose the
            // save, and the sweep picks it up later.
            if (! $this->budget->canStartCall()) {
                return;
            }

            $started = microtime(true);

            $translated = $this->translations->translateQuietly(
                $english ?? (string) $arabic,
                $english !== null ? 'en' : 'ar',
                $english !== null ? 'ar' : 'en',
                $school,
            );

            $this->budget->spend(microtime(true) - $started);

            if ($translated !== null) {
                $model->setAttribute($english !== null ? $arabicKey : $englishKey, $translated);
            }
        }
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
