<?php

declare(strict_types=1);

namespace App\Domain\Localization\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * The one place that knows which columns are bilingual.
 *
 * `config('bilingual.targets')` is the registry; this turns it into the two
 * shapes its callers need — the target list the sweep walks, and a
 * table => column pairs map the on-save fill looks up by table name. Before
 * this existed each mechanism built its own pairs from the config, so "what
 * counts as bilingual" had two definitions that could drift apart.
 */
final class BilingualTargets
{
    /** @var list<array<string, mixed>>|null */
    private static ?array $targets = null;

    /** @var array<string, list<array{en: string, ar: string}>>|null */
    private static ?array $pairs = null;

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$targets ??= array_values(config('bilingual.targets', []));
    }

    /**
     * Column pairs keyed by table name.
     *
     * Keyed by *table* rather than model class on purpose: the app models are
     * thin subclasses of the domain ones (`App\Models\Announcement` extends the
     * domain announcement), and a save only knows the table it is writing.
     *
     * @return array<string, list<array{en: string, ar: string}>>
     */
    public static function pairsByTable(): array
    {
        if (self::$pairs !== null) {
            return self::$pairs;
        }

        $pairs = [];

        foreach (self::all() as $target) {
            foreach ($target['pairs'] as $pair) {
                // Keyed, so a pair written twice in the config is still one pair.
                $pairs[self::table($target)][$pair['en'].'|'.$pair['ar']] = $pair;
            }
        }

        return self::$pairs = array_map('array_values', $pairs);
    }

    /**
     * @return list<array{en: string, ar: string}>
     */
    public static function forTable(string $table): array
    {
        return self::pairsByTable()[$table] ?? [];
    }

    /**
     * The table a target covers: the model is what knows its own table.
     *
     * @param  array<string, mixed>  $target
     */
    public static function table(array $target): string
    {
        $model = $target['model'];

        return (new $model)->getTable();
    }

    /**
     * Drops the memoised registry.
     *
     * Only needed where the configuration changes inside a running process — a
     * test that rewrites `bilingual.targets`, for instance. A request reads it
     * once and keeps it.
     */
    public static function refresh(): void
    {
        self::$targets = null;
        self::$pairs = null;
    }
}
