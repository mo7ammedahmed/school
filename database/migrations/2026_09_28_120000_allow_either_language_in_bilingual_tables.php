<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets either language be the one the operator types.
 *
 * Adding `{column}_ar` made the Arabic side nullable but left the original
 * column NOT NULL, which quietly made the app English-first: saving a news post
 * from the Arabic form failed with "NOT NULL constraint failed: news.title"
 * before any translation could run, and the pages whose English column had a
 * default saved an empty string instead. Both languages are optional now, with
 * "at least one of the pair" enforced by validation and the missing side filled
 * by translation.
 *
 * The list comes from `config('bilingual.targets')`, the same registry the
 * sweep and the on-save fill use, so there is one definition of what is
 * bilingual. Types are read from the live schema rather than restated here,
 * because SQLite rebuilds the table for a change and a text column narrowed to
 * varchar(255) would start truncating.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (config('bilingual.targets', []) as $target) {
            if (! isset($target['model'], $target['pairs'])) {
                continue;
            }

            $table = (new $target['model'])->getTable();

            foreach ($target['pairs'] as $pair) {
                $this->makeNullable($table, $pair['en']);
            }
        }
    }

    /**
     * Deliberately not reversed: records saved in one language only have NULL in
     * the other column by design, so restoring NOT NULL could not succeed without
     * deleting or mangling exactly the data this migration exists to allow.
     */
    public function down(): void
    {
        //
    }

    private function makeNullable(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);

        if ($definition === null || ($definition['nullable'] ?? false) === true) {
            return;
        }

        $isText = str_contains(strtolower((string) ($definition['type_name'] ?? '')), 'text');

        Schema::table($table, function (Blueprint $blueprint) use ($column, $isText): void {
            $column = $isText
                ? $blueprint->text($column)
                : $blueprint->string($column);

            $column->nullable()->change();
        });
    }
};
