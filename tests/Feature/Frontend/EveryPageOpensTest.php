<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Every page opens.
 *
 * The suite proves the things around a page — that the role may reach it, that
 * the component exists, that the route names a real controller method — and
 * none of that notices a controller that throws once it is actually asked for
 * the screen: a missing relation, a null column, a property that only exists on
 * one kind of row. Every route is opened here, as every seeded role, against the
 * demo data the application ships with, and any 5xx is a failure with the URI
 * and the role that produced it.
 *
 * A 403 or a 404 is not this test's business: the role matrix and the route
 * integrity cases own those. The question here is narrower and blunt — does the
 * page answer?
 */
class EveryPageOpensTest extends TestCase
{
    use RefreshDatabase;

    /** Routes that are plumbing rather than pages. */
    private const SKIP = ['_inertia', '_ignition', 'sanctum', 'storage/', 'telescope', 'horizon', 'up'];

    public function test_every_get_route_opens_without_a_server_error(): void
    {
        $this->seed();

        $school = School::query()->firstOrFail();
        $this->app->make(TenantContext::class)->set($school->id);

        $actors = $this->actors();
        $this->assertNotEmpty($actors, 'No seeded role could sign in.');

        // Every role in English, and the bilingual pages a second time in
        // Arabic: the `name` accessors, the localized titles and the interface
        // dictionary only run when the locale is `ar`, and a page that reads
        // `name_ar` is a page that can fail only there.
        $passes = [
            'en' => array_keys($actors),
            'ar' => ['super_admin', 'student', 'guardian'],
        ];

        $failures = [];

        foreach ($passes as $locale => $roles) {
            $opened = 0;

            foreach (Route::getRoutes()->getRoutes() as $route) {
                if (! in_array('GET', $route->methods(), true)) {
                    continue;
                }

                if ($this->skipped($route->uri())) {
                    continue;
                }

                $path = $this->concretePath($route);

                if ($path === null) {
                    continue;
                }

                $opened++;

                foreach ($roles as $role) {
                    $status = $this->actingAs($actors[$role])
                        ->withSession(['locale' => $locale])
                        ->get($path)
                        ->getStatusCode();

                    if ($status >= 500) {
                        $failures[] = sprintf(
                            '%s [%s] as %s (%s) answered %d',
                            $path,
                            $route->getName() ?? 'unnamed',
                            $role,
                            $locale,
                            $status,
                        );
                    }
                }
            }

            $this->assertGreaterThan(120, $opened, "The {$locale} sweep stopped resolving routes; it is no longer a sweep.");
        }

        $this->assertSame([], $failures, "These pages threw when they were opened:\n".implode("\n", $failures));
    }

    /**
     * One signed-in account per seeded role, taken from the demo data itself.
     *
     * @return array<string, User>
     */
    private function actors(): array
    {
        $actors = [];

        foreach (['super_admin', 'school_admin', 'principal', 'registrar', 'teacher', 'accountant', 'student', 'guardian'] as $role) {
            $user = User::query()->whereHas('roles', fn ($query) => $query->where('name', $role))->first();

            if ($user !== null) {
                $actors[$role] = $user;
            }
        }

        return $actors;
    }

    private function skipped(string $uri): bool
    {
        if (str_starts_with($uri, '_')) {
            return true;
        }

        foreach (self::SKIP as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The URI with every model placeholder pointed at a real seeded row.
     *
     * Anything else — a slug, a token, a hash — is not a page that can be opened
     * without knowing what belongs in it, so the route is left out rather than
     * guessed at.
     */
    private function concretePath(RoutingRoute $route): ?string
    {
        $values = [];

        foreach ($route->signatureParameters(['subClass' => Model::class]) as $parameter) {
            $type = $parameter->getType();
            $class = $type instanceof ReflectionNamedType ? $type->getName() : null;

            if ($class === null || ! is_subclass_of($class, Model::class)) {
                return null;
            }

            $record = $class::query()->first();

            if ($record === null) {
                return null;
            }

            $values[$parameter->getName()] = (string) $record->getRouteKey();
        }

        $path = $route->uri();

        foreach ($values as $name => $value) {
            $path = str_replace(['{'.$name.'}', '{'.$name.'?}'], $value, $path);
        }

        return str_contains($path, '{') ? null : '/'.ltrim($path, '/');
    }
}
