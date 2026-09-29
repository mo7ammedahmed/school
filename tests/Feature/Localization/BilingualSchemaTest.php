<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the contract between config/bilingual.php and the database: the
 * automatic translation sweep can only fill a column that exists, so a target
 * that drifts from the schema (or a new `*_ar` column nobody registered) has to
 * fail here rather than quietly do nothing in production.
 */
class BilingualSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_configured_target_points_at_a_real_model_and_scope(): void
    {
        $targets = config('bilingual.targets');

        $this->assertNotEmpty($targets, 'No bilingual targets are configured.');

        foreach ($targets as $target) {
            $label = $target['label'];

            $this->assertTrue(
                class_exists($target['model']),
                "Target \"{$label}\" names a model that does not exist: {$target['model']}",
            );

            /** @var Model $model */
            $model = new $target['model'];
            $table = $model->getTable();

            $this->assertTrue(
                Schema::hasTable($table),
                "Target \"{$label}\" names a table that does not exist: {$table}",
            );

            if ($relation = $target['scope_relation'] ?? null) {
                $this->assertTrue(
                    method_exists($model, (string) $relation),
                    "Target \"{$label}\" scopes through the missing relation \"{$relation}\".",
                );
            } elseif (($target['scope'] ?? 'school_id') !== 'global') {
                // `global` is the sweep's marker for a table that belongs to no
                // school — theme presets are offered platform-wide and there is
                // no column to filter on, so there is nothing to assert here.
                $scope = $target['scope'] ?? 'school_id';

                $this->assertTrue(
                    Schema::hasColumn($table, $scope),
                    "Target \"{$label}\" scopes by \"{$scope}\", which {$table} does not have.",
                );
            }

            foreach ($target['pairs'] as $pair) {
                foreach (['en', 'ar'] as $side) {
                    $this->assertTrue(
                        Schema::hasColumn($table, $pair[$side]),
                        "Target \"{$label}\" uses {$table}.{$pair[$side]}, which does not exist.",
                    );
                }
            }
        }
    }

    public function test_arabic_columns_match_the_storage_type_of_their_english_column(): void
    {
        foreach (config('bilingual.targets') as $target) {
            $table = (new $target['model'])->getTable();
            $columns = collect(Schema::getColumns($table))->keyBy('name');

            foreach ($target['pairs'] as $pair) {
                $english = $columns[$pair['en']] ?? null;
                $arabic = $columns[$pair['ar']] ?? null;

                $this->assertNotNull($english, "{$table}.{$pair['en']} is missing.");
                $this->assertNotNull($arabic, "{$table}.{$pair['ar']} is missing.");

                // A varchar Arabic column would truncate a long English body.
                $this->assertSame(
                    $english['type_name'],
                    $arabic['type_name'],
                    "{$table}.{$pair['ar']} is {$arabic['type_name']} but {$table}.{$pair['en']} is {$english['type_name']}.",
                );
            }
        }
    }

    public function test_every_arabic_column_in_the_database_is_covered_by_the_registry(): void
    {
        $registered = [];

        foreach (config('bilingual.targets') as $target) {
            $table = (new $target['model'])->getTable();

            foreach ($target['pairs'] as $pair) {
                $registered["{$table}.{$pair['ar']}"] = true;
            }
        }

        $uncovered = [];

        foreach (Schema::getTableListing() as $listing) {
            // SQLite reports tables as "main.foo"; the registry uses "foo".
            $table = $this->bareTableName($listing);

            foreach (Schema::getColumns($listing) as $column) {
                if (! str_ends_with($column['name'], '_ar')) {
                    continue;
                }

                if (! isset($registered["{$table}.{$column['name']}"])) {
                    $uncovered[] = "{$table}.{$column['name']}";
                }
            }
        }

        $this->assertSame(
            [],
            $uncovered,
            'Arabic columns exist but no bilingual target covers them: '.implode(', ', $uncovered),
        );
    }

    private function bareTableName(string $table): string
    {
        $position = strrpos($table, '.');

        return $position === false ? $table : substr($table, $position + 1);
    }

    public function test_every_content_table_from_the_arabic_columns_migration_has_its_arabic_twin(): void
    {
        $migration = require database_path('migrations/2026_09_26_180000_add_arabic_columns_to_content_tables.php');

        $this->assertNotNull($migration);

        // The migration is already applied by RefreshDatabase, so the visible
        // proof is that each declared pair exists on the live schema.
        foreach (['announcements', 'news', 'events', 'faqs', 'assignments', 'quizzes', 'invoices'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} does not exist.");

            $arabic = collect(Schema::getColumns($table))
                ->pluck('name')
                ->filter(fn (string $name): bool => str_ends_with($name, '_ar'));

            $this->assertNotEmpty($arabic, "{$table} has no Arabic columns.");
        }
    }
}
