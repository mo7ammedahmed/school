<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests exercise Laravel responses, not the external Node SSR
        // process. The package defaults SSR on even in testing, which conflicts
        // with the suite's fail-closed HTTP fake.
        config(['inertia.ssr.enabled' => false]);

        // The on-save fill declines to run in a console, because that is how an
        // import or a command is kept from translating a thousand rows behind
        // the operator's back. The suite is not a bulk run in that sense: it
        // stands in for the browser, so the flag is on here and the ordinary
        // request path is what the tests exercise — the observer is covered by
        // the same saves the pages make, not only by tests that switch it on
        // for themselves.
        config(['bilingual.autofill_in_console' => true]);

        // No test may reach a translation provider (or anything else outside)
        // by accident: a request with no matching fake fails loudly instead.
        // A catch-all `Http::fake()` would be wrong here — stubs are matched in
        // the order they were registered, so one installed in `setUp` would
        // shadow the url-specific fake a test installs afterwards.
        Http::preventStrayRequests();
    }

    /**
     * Signs in a user who belongs to the school the request is about.
     *
     * Fourteen test classes carried their own copy of this, which is exactly how
     * they drifted: most granted the permissions they named, one only created
     * them, one pinned the user's locale, two took the school from a property.
     * One definition with those differences as arguments is what keeps a test
     * suite's fixtures comparable between files.
     *
     * @param  list<string>  $permissions  permissions to create if missing and grant
     * @param  array<string, mixed>  $attributes  extra attributes for the user factory
     */
    protected function actingAsSchoolUser(School $school, array $permissions = [], array $attributes = []): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create($attributes);

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        // Pin the tenant, as `school.context` would on a real request. Tests
        // that query school-owned models before firing an HTTP call would
        // otherwise be looking at nothing, which is the correct fail-closed
        // answer but not the one a signed-in user's unit of work gets.
        $this->app->make(TenantContext::class)->set($school->id);

        return $user;
    }
}
