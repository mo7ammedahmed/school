<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * A query that names a column the table does not have loses its rows silently.
 *
 * The invoice form was the visible case: the controller asked for
 * `student_number`, which `students` has never had, so on SQLite the quoted
 * identifier was read as a *string literal* and every row returned the word
 * "student_number" in a column named after it. Nothing errored, and the picker
 * beside it rendered sixty blank options because the page read `student.name`,
 * which no payload supplied. The same shape hid the public news and events
 * pages, which filtered on `publish_date` and `event_date`, and so matched
 * nothing at all — every article 404ed.
 *
 * The write side is already covered (`PayloadContractTest`): a validated key
 * that cannot be stored. This is the read side: a column a controller reads
 * that no table holds.
 *
 * Only queries a person can read at a glance are checked — one model per
 * statement, no relation closures, no raw SQL — because a guard that guesses
 * wrong about a nested subquery is worse than no guard, and would be deleted.
 */
class QueryColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_column_a_controller_queries_exists_on_its_table(): void
    {
        $offenders = [];

        foreach ($this->controllerFiles() as $path) {
            $source = file_get_contents($path);

            foreach ($this->chains($source) as [$model, $chain]) {
                $table = (new $model)->getTable();
                $columns = array_column(Schema::getColumns($table), 'name');

                foreach ($this->columnsNamedIn($chain) as $column) {
                    if (in_array($column, $columns, true)) {
                        continue;
                    }

                    $offenders[] = sprintf(
                        '%s queries %s.%s, which does not exist',
                        $this->shortPath($path),
                        $table,
                        $column,
                    );
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($offenders)),
            "A column that is not there returns nothing, or the wrong thing:\n  ".implode("\n  ", array_unique($offenders)),
        );
    }

    /**
     * The query chains in a controller: `[model, "->where(...)->get([...])"]`.
     *
     * A chain is the text from one model reference to the end of its statement,
     * which is why the split is on semicolons: everything before the reference
     * (a method signature, a previous line) is not part of the query.
     *
     * Two things are removed rather than reported. `with(...)` names relations,
     * not columns, so its arguments are stripped. A closure (`function` or arrow
     * `fn`) can switch to another table mid-chain, so the chain ends there — a
     * shorter chain finds fewer columns, which is the safe direction: this test
     * only ever reports a column a table demonstrably does not have.
     *
     * @return list<array{0: class-string<Model>, 1: string}>
     */
    private function chains(string $source): array
    {
        $chains = [];

        foreach (preg_split('/;/', $source) ?: [] as $statement) {
            if (! preg_match('/([A-Z]\w+)::/', $statement, $first, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            if (count(array_unique(preg_match_all('/([A-Z]\w+)::/', $statement, $all) ? $all[1] : [])) !== 1) {
                continue;
            }

            $class = self::knownModels()[$first[1][0]] ?? null;

            if ($class === null || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            $chain = substr($statement, $first[1][1]);

            // `with('relation')` and `with(['a', 'b'])` name relations.
            $chain = preg_replace(
                '/->(?:with|withCount|withSum|withAvg|withMin|withMax|load|loadCount|withTrashed|onlyTrashed)\(\s*(\[[^\]]*\]|[\'"][^\'"]*[\'"])\)/',
                '',
                $chain,
            ) ?? $chain;

            $chain = preg_split('/\b(function|fn)\b/', $chain, 2)[0] ?? $chain;

            $chains[] = [$class, $chain];
        }

        return $chains;
    }

    /**
     * Methods whose *first* argument names a column and whose later arguments are
     * values: `->where('status', 'paid')`, `->orderBy('name_en', 'desc')`,
     * `->whereIn('status', ['paid', 'overdue'])`.
     */
    private const array COLUMN_FIRST_METHODS = [
        'where', 'orWhere', 'whereIn', 'orWhereIn', 'whereNotIn', 'whereNull', 'whereNotNull',
        'whereDate', 'whereMonth', 'whereYear', 'whereBetween', 'orderBy', 'orderByDesc',
        'latest', 'oldest', 'groupBy', 'having', 'pluck', 'value',
    ];

    /**
     * Methods whose every string literal is a column: `->select(['a', 'b'])`,
     * `->get(['id', 'name'])`.
     */
    private const array COLUMN_LIST_METHODS = ['select', 'addSelect', 'get'];

    /**
     * Column names appearing in the statement.
     *
     * Only arguments that are pure string literals count, so `whereRaw`, `orderBy(DB::raw(...))`
     * and a column passed as a variable are all ignored rather than guessed at.
     *
     * @return list<string>
     */
    private function columnsNamedIn(string $statement): array
    {
        $columns = [];

        $first = implode('|', self::COLUMN_FIRST_METHODS);
        $lists = implode('|', self::COLUMN_LIST_METHODS);

        // A list argument: `->get(['a', 'b'])` or `->select('a', 'b')`.
        if (preg_match_all('/->('.$lists.')\(\s*(\[[^\]]*\]|[\'"][^\'"]*[\'"](?:\s*,\s*[\'"][^\'"]*[\'"])*)/', $statement, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columns = [...$columns, ...$this->literalsIn($match[2])];
            }
        }

        // A first argument only: everything after it is a value or a direction.
        if (preg_match_all('/->('.$first.')\(\s*[\'"]([a-z_][a-z0-9_.]*)[\'"]/', $statement, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columns[] = $this->columnOf($match[2]);
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * `students.first_name` names the table too; the column is the part after
     * the dot.
     */
    private function columnOf(string $literal): string
    {
        return str_contains($literal, '.') ? substr($literal, strrpos($literal, '.') + 1) : $literal;
    }

    /**
     * @return list<string>
     */
    private function literalsIn(string $arguments): array
    {
        $literals = [];

        if (preg_match_all('/[\'"]([a-z_][a-z0-9_.]*)[\'"]/i', $arguments, $matches)) {
            foreach ($matches[1] as $literal) {
                $literals[] = $this->columnOf($literal);
            }
        }

        return $literals;
    }

    /** @var array<string, class-string>|null */
    private static ?array $models = null;

    /**
     * Short class name => fully qualified name, for every model in the app.
     *
     * @return array<string, class-string>
     */
    private static function knownModels(): array
    {
        if (self::$models !== null) {
            return self::$models;
        }

        $models = [];

        foreach (self::phpFiles(app_path()) as $file) {
            $source = file_get_contents($file);

            if (! preg_match('/^namespace\s+([^;]+);/m', $source, $namespace)) {
                continue;
            }

            if (! preg_match('/^class\s+(\w+)\s+extends\s+([\w\\\\]+)/m', $source, $class)) {
                continue;
            }

            $models[$class[1]] ??= $namespace[1].'\\'.$class[1];
        }

        return self::$models = $models;
    }

    /** @return list<string> */
    private function controllerFiles(): array
    {
        return self::phpFiles(app_path('Http/Controllers'));
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function shortPath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
