<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Domain\Schools\Support\TenantContext;
use Tests\TestCase;

/**
 * The tenant context is pinned for one unit of work, and the unit of work is
 * not the process.
 *
 * Each request in a classic deployment gets its own PHP process, so a pin that
 * is never cleared cannot outlive the request that made it. Laravel Cloud runs
 * the application on Octane (FrankenPHP), where one worker answers many
 * requests, and a pin that outlives its request becomes the next request's
 * answer — including for a public page that never proved a membership. These
 * tests hold the context to that smaller contract: pinned within a unit of
 * work, gone when the runtime forgets scoped instances, which is exactly what
 * Octane does between requests and the queue worker does between jobs.
 */
class TenantContextLifecycleTest extends TestCase
{
    public function test_the_context_is_pinned_for_the_whole_unit_of_work(): void
    {
        $context = app(TenantContext::class);

        $context->set(7);

        $this->assertSame($context, app(TenantContext::class), 'the pin must be the same object everywhere');
        $this->assertSame(7, app(TenantContext::class)->id());
    }

    public function test_the_pin_is_gone_when_the_runtime_forgets_scoped_instances(): void
    {
        app(TenantContext::class)->set(7);

        // What Octane's FlushTemporaryContainerInstances listener calls between
        // requests, and the queue worker calls between jobs.
        $this->app->forgetScopedInstances();

        $this->assertNull(
            app(TenantContext::class)->id(),
            'a pin must not survive into the next request on a long-lived worker',
        );
    }

    public function test_an_explicit_null_pin_is_also_forgotten(): void
    {
        app(TenantContext::class)->set(null);

        $this->app->forgetScopedInstances();

        $this->assertFalse(app(TenantContext::class)->hasId());
    }
}
