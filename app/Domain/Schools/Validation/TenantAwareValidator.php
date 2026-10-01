<?php

declare(strict_types=1);

namespace App\Domain\Schools\Validation;

use App\Domain\Schools\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

/**
 * Scopes `exists:` and `unique:` rules to the school the work is about.
 *
 * `exists:students,id` asks "does this id exist anywhere", which is exactly the
 * question a foreign id passes. There are more than a hundred such rules across
 * controllers, form requests and DTOs; editing them by hand fixes today's list
 * and lets tomorrow's rule be written unscoped again. The validation factory is
 * resolved through this class instead, so any string rule naming a table with a
 * `school_id` column is narrowed to the active tenant automatically, with no
 * knowledge needed at the call site.
 *
 * The scope fails closed in the same way the model scope does: with no tenant
 * context the rule is asked about `school_id = null`, which matches nothing, so
 * a rule that forgets the tenant cannot become a cross-school accept.
 *
 * `Rule::exists()` / `Rule::unique()` objects bypass this: they do not run
 * through `validateExists` / `validateUnique`, so those call sites must scope
 * themselves. `TenantValidationRuleScopeTest` scans the source and fails when
 * one of them does not.
 */
final class TenantAwareValidator extends Validator
{
    /** @var array<string, bool> table => has school_id, resolved once per process */
    private static array $tenantTables = [];

    /** The parsed table of the unique rule currently being validated. */
    private ?string $uniqueTable = null;

    public function validateUnique($attribute, $value, $parameters)
    {
        $this->uniqueTable = $this->parseTenantTable((string) ($parameters[0] ?? ''));

        return parent::validateUnique($attribute, $value, $parameters);
    }

    protected function getExistCount($connection, $table, $column, $value, $parameters)
    {
        // `exists` extras begin at index 2; appending at the end pairs the extra
        // condition the same way the documented `exists:table,column,where,value`
        // syntax does.
        return parent::getExistCount(
            $connection,
            $table,
            $column,
            $value,
            $this->scopeToTenant($parameters, (string) $table),
        );
    }

    /**
     * @param  array<int, int|string|null>  $parameters
     * @return array<int, int|string|null>
     */
    private function scopeToTenant(array $parameters, string $table): array
    {
        if (! $this->isTenantTable($table)) {
            return $parameters;
        }

        $parameters[] = 'school_id';
        $parameters[] = $this->tenantId();

        return $parameters;
    }

    /**
     * Unique rules reach their extra conditions through this method rather than
     * through the parameter list, because the id to ignore sits between the
     * column and the conditions.
     *
     * @param  array<int, int|string|null>  $parameters
     * @return array<string, mixed>
     */
    protected function getUniqueExtra($parameters)
    {
        $extra = parent::getUniqueExtra($parameters);

        if ($this->uniqueTable !== null
            && $this->isTenantTable($this->uniqueTable)
            && ! array_key_exists('school_id', $extra)) {
            $extra['school_id'] = $this->tenantId();
        }

        return $extra;
    }

    /**
     * The tenant id the rule must match, as a string so it can be carried in the
     * rule's parameter list; null is deliberate and matches nothing.
     */
    private function tenantId(): ?string
    {
        $id = app(TenantContext::class)->id();

        return $id === null ? null : (string) $id;
    }

    private function isTenantTable(string $table): bool
    {
        if (! isset(self::$tenantTables[$table])) {
            self::$tenantTables[$table] = Schema::hasColumn($table, 'school_id');
        }

        return self::$tenantTables[$table];
    }

    /**
     * Strip a connection prefix (`mysql.students`) so the schema check looks at
     * the table name.
     */
    private function parseTenantTable(string $table): string
    {
        return str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
    }
}
