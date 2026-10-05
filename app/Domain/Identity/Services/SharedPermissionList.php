<?php

declare(strict_types=1);

namespace App\Domain\Identity\Services;

use App\Models\User;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Spatie\Permission\PermissionRegistrar;

/**
 * The permission list Inertia ships to the browser, built once per user.
 *
 * The application is client-side, so this list crosses the wire on *every*
 * navigation. Building it is a three-query union of the user's direct grants and
 * the grants of every role they hold, and Spatie's permission cache does not
 * help: it caches the role→permission relation, while the per-user union is
 * rebuilt from scratch each time the session guard re-resolves the model — which
 * is once per request. The cost was therefore paid on every page load, for a
 * list that almost never changes.
 *
 * **Invalidation is the whole design.** A time-to-live cache is wrong here in
 * both directions, and one of them is a security problem rather than a stale
 * screen: a permission *revoked* would keep being offered to the browser until
 * the entry expired, and a permission *granted* would be missing for the same
 * window. So the entry is dropped whenever a role or permission assignment
 * changes, rather than being left to time out.
 */
final class SharedPermissionList
{
    /**
     * Deliberately short. Invalidation is event-driven, so the TTL is only a
     * backstop against an entry surviving a process that wrote without going
     * through the invalidating path — not the mechanism that makes it correct.
     */
    private const TTL_SECONDS = 300;

    public function __construct(
        private readonly CacheFactory $cache,
        private readonly PermissionRegistrar $permissions,
    ) {}

    /**
     * @return list<string>
     */
    public function for(User $user): array
    {
        $key = $this->keyFor($user);

        /** @var list<string>|null $cached */
        $cached = $this->store()->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $names = $user->getAllPermissions()->pluck('name')->values()->all();

        /** @var list<string> $names */
        $this->store()->put($key, $names, self::TTL_SECONDS);

        return $names;
    }

    /**
     * Drop one user's entry.
     *
     * Called on every assignment change. A user with no entry costs nothing, so
     * this needs no "did it exist" check — and that matters, because a guard
     * clause here would silently skip the invalidation that correctness depends
     * on.
     */
    public function forget(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $this->store()->forget($this->keyFor($user));
    }

    /**
     * Drop every entry.
     *
     * A role's permission set changing alters the list of every user holding
     * it, and there is no cheap way to enumerate them, so the seeder and any
     * console command that rewrites the catalogue clears the lot. Sign-ins pay
     * one rebuild each.
     */
    public function flush(): void
    {
        $this->permissions->forgetCachedPermissions();
        $this->store()->forever('user-permissions:version', bin2hex(random_bytes(16)));
    }

    /**
     * The default store, resolved on every call.
     *
     * `CacheRepository` is bound to whatever the *default* store was when the
     * container built, so a long-lived service holding one keeps writing to that
     * snapshot even after the configuration changes. Going through the factory
     * means reads and writes always agree on the same store — which is not a
     * theoretical concern: a read from one store and an invalidation to another
     * is a cache that can never be invalidated, and that is exactly the bug
     * this class exists to avoid.
     */
    private function store(): CacheRepository
    {
        return $this->cache->store();
    }

    private function keyFor(User $user): string
    {
        // The guard name is in the key because the same id can hold different
        // roles under different guards, and a shared entry would hand one guard
        // the other's privileges.
        //
        // The guard comes from the *model's* configured default rather than the
        // currently attached roles. Reading it from the loaded relation is a
        // trap here: invalidation runs immediately after a role change, at
        // which point the relation may already have been re-pointed at the new
        // role, so the key computed on the way out can differ from the one
        // stored on the way in and the entry survives.
        $guard = (string) config('auth.defaults.guard', 'web');

        $version = $this->store()->get('user-permissions:version', 'initial');

        return 'user-permissions:'.$version.':'.$user->getAuthIdentifier().':'.$guard;
    }
}
