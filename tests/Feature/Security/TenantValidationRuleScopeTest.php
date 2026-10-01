<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Finance\Models\Invoice;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Domain\Schools\Validation\TenantAwareValidator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * A foreign primary key must not validate.
 *
 * `exists:` and `unique:` rules are the other half of tenant isolation: a form
 * that references a row by id and validates only "it exists somewhere" will
 * happily accept another school's student, section or invoice.
 *
 * String rules are covered structurally — the validation factory resolves
 * {@see TenantAwareValidator}, which narrows every string rule that names a
 * table with a `school_id` column to the active tenant. The first four tests
 * here prove that at run time rather than trusting the source.
 *
 * `Rule::exists()` / `Rule::unique()` objects bypass the validator, so the scan
 * below is what keeps them honest: any object rule naming a tenant table must
 * carry its own school filter, and the few that intentionally do not are named
 * with their reason.
 */
class TenantValidationRuleScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tenant-table object rules that are intentionally platform-wide.
     *
     * Keyed `path|table|kind`. Each one is a statement about why the row is not
     * tenant-owned even though its table has the column.
     *
     * @var array<string, string>
     */
    private const EXEMPT = [
        // Filled in only where a rule genuinely crosses schools; every entry
        // needs a reason, not just a name.
    ];

    public function test_a_string_exists_rule_is_scoped_to_the_tenant(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $mine = Student::factory()->create(['school_id' => $school->id]);
        $foreign = Student::factory()->create(['school_id' => $other->id]);

        app(TenantContext::class)->set($school->id);

        $this->assertFalse(
            Validator::make(['student_id' => $mine->id], ['student_id' => 'exists:students,id'])->fails(),
        );
        $this->assertTrue(
            Validator::make(['student_id' => $foreign->id], ['student_id' => 'exists:students,id'])->fails(),
        );
    }

    public function test_a_string_unique_rule_is_scoped_to_the_tenant(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        Invoice::factory()->create(['school_id' => $school->id, 'invoice_number' => 'INV-0001']);
        Invoice::factory()->create(['school_id' => $other->id, 'invoice_number' => 'INV-0002']);

        app(TenantContext::class)->set($school->id);

        // Another school's number is free here; this school's own is not.
        $this->assertFalse(
            Validator::make(['invoice_number' => 'INV-0002'], ['invoice_number' => 'unique:invoices,invoice_number'])->fails(),
        );
        $this->assertTrue(
            Validator::make(['invoice_number' => 'INV-0001'], ['invoice_number' => 'unique:invoices,invoice_number'])->fails(),
        );
    }

    public function test_a_string_rule_against_a_platform_table_is_not_scoped(): void
    {
        $user = User::factory()->create();

        app(TenantContext::class)->set(School::factory()->create()->id);

        $this->assertFalse(
            Validator::make(['user_id' => $user->id], ['user_id' => 'exists:users,id'])->fails(),
        );
    }

    public function test_a_string_rule_with_no_tenant_context_matches_nothing(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);

        app(TenantContext::class)->forget();

        $this->assertTrue(
            Validator::make(['student_id' => $student->id], ['student_id' => 'exists:students,id'])->fails(),
            'a rule with no tenant must not accept any school\'s row',
        );
    }

    public function test_every_object_rule_against_a_tenant_table_is_school_scoped(): void
    {
        $unscoped = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getRelativePathname());
            $source = File::get($file->getPathname());

            foreach ($this->classRules($source) as $rule) {
                $this->collect($unscoped, $path, $source, $rule['offset'], $rule['table'], $rule['kind'], $rule['scoped']);
            }
        }

        $this->assertSame(
            [],
            $unscoped,
            "These Rule::exists/Rule::unique calls accept a foreign school's row:\n".implode("\n", $unscoped),
        );
    }

    /**
     * `Rule::exists('table', …)->where('school_id', …)` and `Rule::unique(...)`.
     *
     * The whole call is taken (balanced parentheses), not the line, because the
     * school filter is usually on the next line of a chained rule.
     *
     * @return list<array{offset: int, table: string, kind: string, scoped: bool}>
     */
    private function classRules(string $source): array
    {
        preg_match_all(
            "/Rule::(exists|unique)\\(\\s*'([a-z_]+)'/",
            $source,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        $rules = [];

        foreach ($matches[0] as $index => [$whole, $offset]) {
            $call = $this->balancedCall($source, $offset + strlen($whole) - 1);

            $rules[] = [
                'offset' => $offset,
                'table' => $matches[2][$index][0],
                'kind' => $matches[1][$index][0],
                'scoped' => str_contains($call, 'school_id'),
            ];
        }

        return $rules;
    }

    /**
     * From the opening parenthesis at `$position`, return through its match.
     */
    private function balancedCall(string $source, int $position): string
    {
        $depth = 0;
        $length = strlen($source);

        for ($i = $position; $i < $length; $i++) {
            if ($source[$i] === '(') {
                $depth++;
            } elseif ($source[$i] === ')') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $position, $i - $position + 1);
                }
            }
        }

        return substr($source, $position);
    }

    /**
     * @param  list<string>  $unscoped
     */
    private function collect(array &$unscoped, string $path, string $source, int $offset, string $table, string $kind, bool $scoped): void
    {
        if ($scoped || ! Schema::hasColumn($table, 'school_id')) {
            return;
        }

        $key = $path.'|'.$table.'|'.$kind;

        /** @var array<string, string> $exempt */
        $exempt = self::EXEMPT;

        if (array_key_exists($key, $exempt)) {
            return;
        }

        $line = substr_count(substr($source, 0, $offset), "\n") + 1;

        $unscoped[] = sprintf('  %s:%d  Rule::%s(%s)', $path, $line, $kind, $table);
    }
}
