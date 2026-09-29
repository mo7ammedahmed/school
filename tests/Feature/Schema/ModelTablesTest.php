<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Tests\TestCase;
use Throwable;

/**
 * A model whose class name does not match its table asks for a table nobody made.
 *
 * Four aliases had this: `App\Models\Teacher` is a `TeacherProfile`, but Eloquent
 * derives `teachers` from the class name, and the table is `teacher_profiles`.
 * The public teachers page therefore died on "no such table: teachers" — not a
 * blank field, an error page. `Attendance` (→ `attendances`), `Classroom`
 * (→ `classrooms`) and `Timetable` (→ `timetables`) were the same trap for
 * `attendance_records`, `rooms` and `timetable_entries`.
 *
 * Every model in the app is resolved here and asked for its table. A model may
 * point anywhere it likes — it just has to point at something that is there.
 */
class ModelTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_model_resolves_to_a_table_that_exists(): void
    {
        $offenders = [];

        foreach ($this->modelClasses() as $class) {
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            try {
                $table = (new $class)->getTable();
            } catch (Throwable $exception) {
                $offenders[] = sprintf('%s could not be instantiated: %s', $class, $exception->getMessage());

                continue;
            }

            if (Schema::hasTable($table)) {
                continue;
            }

            $offenders[] = sprintf('%s reads table "%s", which does not exist', $class, $table);
        }

        $this->assertSame(
            [],
            $offenders,
            "A model pointing at a missing table fails at the first query:\n  ".implode("\n  ", $offenders),
        );
    }

    /**
     * Every concrete Eloquent model declared in the app.
     *
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        $classes = [];

        foreach (['Models', 'Domain'] as $directory) {
            foreach (self::phpFiles(app_path($directory)) as $file) {
                $source = file_get_contents($file);

                if (! preg_match('/^namespace\s+([^;]+);/m', $source, $namespace)) {
                    continue;
                }

                if (! preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $source, $class)) {
                    continue;
                }

                $candidate = $namespace[1].'\\'.$class[1];

                if (! class_exists($candidate) || ! is_subclass_of($candidate, Model::class)) {
                    continue;
                }

                $classes[$candidate] = $candidate;
            }
        }

        return array_values($classes);
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
}
