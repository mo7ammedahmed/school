<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * RoleSeeder builds the five staff roles as "every permission, minus a deny
 * list". That is convenient to read and easy to get wrong: a permission added
 * to the catalogue is handed to every staff role whose deny list does not name
 * it, in the same commit, silently.
 *
 * These cases pin the outcome for the permissions that must *not* travel, so
 * the next person to add one finds out from a red test rather than from a
 * support ticket about a teacher who can refund a payment.
 */
class SeededRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * Permissions that must never reach a role other than the ones named.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function restrictedGrants(): array
    {
        return [
            'fee discounts are finance and office work' => [
                'manage-discounts',
                ['super_admin', 'school_admin', 'principal', 'registrar', 'accountant'],
            ],
            'the audit trail is supervisory' => [
                'view-audit-logs',
                ['super_admin', 'school_admin', 'principal'],
            ],
        ];
    }

    #[DataProvider('restrictedGrants')]
    public function test_a_restricted_permission_reaches_only_its_roles(string $permission, array $expectedRoles): void
    {
        $this->assertTrue(
            Permission::where('name', $permission)->exists(),
            "The permission [{$permission}] is not seeded, so no role could ever hold it.",
        );

        $holders = Role::all()
            ->filter(fn (Role $role): bool => $role->hasPermissionTo($permission))
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $expected = collect($expectedRoles)->sort()->values()->all();

        $this->assertSame($expected, $holders);
    }

    /**
     * The trap itself: a catalogue addition must not become a universal grant.
     *
     * @return array<string, array{string}>
     */
    public static function rolesWithNoAdministrativePermissions(): array
    {
        return [
            'teacher' => ['teacher'],
            'student' => ['student'],
            'guardian' => ['guardian'],
        ];
    }

    #[DataProvider('rolesWithNoAdministrativePermissions')]
    public function test_a_restricted_permission_is_denied_to_the_role(string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $this->assertFalse(
            $role->hasPermissionTo('manage-discounts'),
            "The [{$roleName}] role must not be able to change fee discounts.",
        );

        $this->assertFalse(
            $role->hasPermissionTo('view-audit-logs'),
            "The [{$roleName}] role must not be able to read the audit trail.",
        );
    }

    public function test_every_seeded_permission_is_held_by_at_least_one_role(): void
    {
        $orphans = Permission::all()
            ->reject(fn (Permission $permission): bool => Role::all()->contains(
                fn (Role $role): bool => $role->hasPermissionTo($permission->name),
            ))
            ->pluck('name');

        $this->assertSame([], $orphans->all(), 'A seeded permission that no role holds is a typo or a forgotten deny list.');
    }
}
