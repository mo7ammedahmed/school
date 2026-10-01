<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Identity\Services\SharedPermissionList;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * `getAllPermissions()` is a union across every role the user holds, and
 * Inertia ships that list to the browser on *every* page load. Spatie caches the
 * role→permission relation, but the per-user union is rebuilt from a fresh query
 * each time, so the cost is paid again on every navigation of an application
 * that is entirely client-side — which is to say, on every navigation.
 *
 * The list is also a privilege statement, so a cache that outlives a role change
 * is worse than no cache: the browser would keep offering actions the user can no
 * longer perform, and a permission *added* would go missing until the entry
 * expired. Both directions have to be covered, and the second is the one a
 * time-to-live alone gets wrong.
 */
class SharedPermissionListIsCachedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // The suite runs on the `array` cache driver, whose store is rebuilt
        // whenever the application is. A test about a cache needs one that
        // survives a request, or "the list is cached" and "the list was rebuilt"
        // are indistinguishable. Production uses Redis, so this is closer to
        // the real thing, not further from it.
        config()->set('cache.default', 'file');
    }

    public function test_the_permission_list_is_not_rebuilt_from_the_database_on_every_request(): void
    {
        $user = $this->signedInAs('registrar');

        // First navigation: builds the list and populates the cache. A cold
        // cache legitimately costs three queries, and measuring that would be
        // measuring the wrong request — the claim is about every *subsequent*
        // one, which in a client-side app is every navigation.
        $this->assertContains('manage-students', $this->permissionsSharedFor($user));

        $queries = $this->recordRbacQueries(function () use ($user): void {
            // A fresh instance is what the next request sees: the session guard
            // re-resolves the user on every navigation, so nothing memoised on
            // the previous instance survives.
            //
            // The call is to the service, not to `getAllPermissions()`, because
            // the cache lives in the service. Calling the model directly would
            // measure Spatie rather than this change, and would pass or fail for
            // reasons that have nothing to do with the code under test.
            app(SharedPermissionList::class)->for(User::findOrFail($user->id));
        });

        $this->assertSame(
            [],
            $queries,
            'Rebuilding the shared permission list on an already-warm cache cost '.count($queries)
            ." queries:\n  ".implode("\n  ", $queries)
            ."\nSpatie caches the role→permission relation but the per-user union is rebuilt from a "
            .'fresh instance on every navigation, which is every navigation in a client-side app.',
        );
    }

    /**
     * The seam that matters: the *second* share must cost nothing.
     *
     * Reading the cache directly would prove the cache works while leaving the
     * middleware free to bypass it. The payload crossing the wire is what the
     * browser actually pays for, so that is what is measured.
     */
    public function test_the_second_share_of_the_page_costs_nothing(): void
    {
        $user = $this->signedInAs('registrar');

        $this->permissionsSharedFor($user);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $user->currentMembership->school_id);

        $queries = $this->recordRbacQueries(function (): void {
            $this->get('/dashboard');
        });

        $this->assertSame(
            [],
            $queries,
            'A repeat page load queried the RBAC tables '.count($queries)." time(s):\n  "
            .implode("\n  ", $queries)
            ."\nThe shared permission list is supposed to be served from the per-user cache.",
        );
    }

    /**
     * @param  callable():void  $work
     * @return list<string>
     */
    private function recordRbacQueries(callable $work): array
    {
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $sql = $query->sql;

            foreach (['model_has_roles', 'model_has_permissions', 'role_has_permissions', 'permissions', 'roles'] as $table) {
                if (str_contains(strtolower($sql), $table)) {
                    $queries[] = $sql;

                    return;
                }
            }
        });

        $work();

        return $queries;
    }

    public function test_a_role_change_takes_effect_immediately_rather_than_after_the_cache_expires(): void
    {
        $user = $this->signedInAs('registrar');

        $before = $this->permissionsSharedFor($user);
        $this->assertContains('manage-students', $before, 'The registrar role should hold manage-students to begin with.');

        // The exact mutation the Users screen performs.
        $user->syncRoles([Role::findByName('teacher', 'web')->id]);

        $after = $this->permissionsSharedFor($user);

        $this->assertNotContains(
            'manage-students',
            $after,
            'A role change did not invalidate the cached permission list. The browser would keep showing '
            .'the student screen to a user who can no longer open it.',
        );
    }

    public function test_a_newly_granted_permission_appears_without_waiting_for_expiry(): void
    {
        $user = $this->signedInAs('teacher');

        $this->assertNotContains('manage-students', $this->permissionsSharedFor($user));

        $user->givePermissionTo('manage-students');

        $this->assertContains(
            'manage-students',
            $this->permissionsSharedFor($user),
            'A permission granted after the first load did not appear. A time-to-live cache fails this '
            .'direction, which is why the entry has to be keyed and invalidated on write.',
        );
    }

    public function test_a_directly_granted_permission_is_revoked_immediately(): void
    {
        $user = $this->signedInAs('teacher');

        // Granted *directly*, not through the role: `revokePermissionTo` only
        // removes direct grants, so revoking one the role supplies would be a
        // test asserting nothing about the cache.
        $user->givePermissionTo('manage-students');

        $this->assertContains('manage-students', $this->permissionsSharedFor($user));

        $user->revokePermissionTo('manage-students');

        $this->assertNotContains(
            'manage-students',
            $this->permissionsSharedFor($user),
            'A revoked direct permission survived in the cached list, so the browser would keep offering '
            .'a screen the user can no longer open.',
        );
    }

    /**
     * The list is a privilege statement, so it must be exactly the user's own
     * permissions — never a superset from a neighbour's cache entry, and never a
     * permission the user does not hold.
     */
    public function test_one_users_cached_list_never_leaks_into_anothers(): void
    {
        $registrar = $this->signedInAs('registrar');
        $teacher = $this->signedInAs('teacher');

        $registrarList = $this->permissionsSharedFor($registrar);
        $teacherList = $this->permissionsSharedFor($teacher);

        $this->assertNotSame(
            $registrarList,
            $teacherList,
            'Two different roles were served the same cached list, so one user is seeing another user\'s '
            .'permissions.',
        );

        $this->assertNotContains('manage-students', $teacherList);
    }

    // ------------------------------------------------------------------

    /**
     * The list the middleware actually shares, read from the rendered Inertia
     * payload rather than from a private method — the seam that matters is the
     * one the browser sees.
     *
     * @return list<string>
     */
    private function permissionsSharedFor(User $user): array
    {
        $this->actingAs($user);
        $this->app['session']->put('school_id', $user->currentMembership->school_id);

        $response = $this->get('/dashboard');

        $response->assertSuccessful();

        $html = (string) $response->getContent();

        // Inertia ships the page as a JSON payload in a
        // `<script type="application/json" data-page>` block. The `data-page`
        // attribute on the root `<div>` is just the element id, so matching on
        // the attribute alone finds "app" and nothing else.
        if (preg_match('#<script[^>]*type="application/json"[^>]*>(.*?)</script>#s', $html, $matches) !== 1) {
            $this->fail('The dashboard did not render an Inertia page: no JSON page payload was present.');
        }

        $page = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

        // A non-Inertia response (a redirect, an error page) cannot answer this
        // question, and guessing would make the test pass for the wrong reason.
        $this->assertIsArray($page, 'The JSON page payload did not decode into an Inertia page.');

        $permissions = $page['props']['auth']['user']['permissions'] ?? null;

        $this->assertIsArray($permissions, 'The shared payload carries no permission list at all.');

        return $permissions;
    }

    private function signedInAs(string $roleName): User
    {
        $school = School::factory()->create();
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->assignRole(Role::findByName($roleName, 'web'));

        return $user;
    }
}
