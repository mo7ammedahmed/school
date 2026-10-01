<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Domain\Identity\Services\SharedPermissionList;
use Spatie\Permission\Traits\HasRoles;

/**
 * Drops the cached permission list whenever a role or permission assignment
 * changes.
 *
 * {@see SharedPermissionList} caches the list Inertia ships to the browser, and
 * a cache that is only invalidated at the call sites is a cache that is wrong
 * the first time somebody assigns a role from a controller nobody updated. So
 * the invalidation is attached to the mutators themselves.
 *
 * Spatie's `HasRoles` and `HasPermissions` declare these methods directly, which
 * is why they cannot simply be overridden and delegated to `parent::` — there is
 * no parent to call. `insteadof` resolves them to renamed copies, so the Spatie
 * implementation is preserved under a private alias and this trait's version
 * wins the ordinary name.
 *
 * Signatures are copied verbatim from the installed Spatie version rather than
 * guessed, because a mismatch here is a fatal at the call site rather than a
 * silent behaviour change.
 */
trait InvalidateSharedPermissionList
{
    /**
     * Spatie's `HasRoles` already composes `HasPermissions`, so all six mutators
     * live in that one trait — aliasing them from both would collide on the
     * permission methods. Aliasing only `HasRoles` keeps a renamed copy of every
     * method this trait needs to delegate to.
     */
    use HasRoles {
        assignRole as spatieAssignRole;
        syncRoles as spatieSyncRoles;
        removeRole as spatieRemoveRole;
        givePermissionTo as spatieGivePermissionTo;
        revokePermissionTo as spatieRevokePermissionTo;
        syncPermissions as spatieSyncPermissions;
    }

    public function assignRole(...$roles): static
    {
        $this->spatieAssignRole(...$roles);

        $this->forgetSharedPermissionList();

        return $this;
    }

    public function syncRoles(...$roles): static
    {
        $this->spatieSyncRoles(...$roles);

        $this->forgetSharedPermissionList();

        return $this;
    }

    public function removeRole(...$role): static
    {
        $this->spatieRemoveRole(...$role);

        $this->forgetSharedPermissionList();

        return $this;
    }

    public function givePermissionTo(...$permissions): static
    {
        $this->spatieGivePermissionTo(...$permissions);

        $this->forgetSharedPermissionList();

        return $this;
    }

    public function revokePermissionTo($permission): static
    {
        $this->spatieRevokePermissionTo($permission);

        $this->forgetSharedPermissionList();

        return $this;
    }

    public function syncPermissions(...$permissions): static
    {
        $this->spatieSyncPermissions(...$permissions);

        $this->forgetSharedPermissionList();

        return $this;
    }

    private function forgetSharedPermissionList(): void
    {
        // Bound-ness is checked rather than assumed: the model is also used from
        // seeders and console commands that boot without the HTTP bindings, and
        // a missing container binding must not fail a role assignment.
        if (! app()->bound(SharedPermissionList::class)) {
            return;
        }

        app(SharedPermissionList::class)->forget($this);
    }
}
