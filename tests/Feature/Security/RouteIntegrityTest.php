<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Two routing defects are invisible at the call site and loud in production.
 *
 * A route pointing at a method that does not exist answers every allowed role
 * with a 500, and the authorization matrix cannot interpret that — it was
 * excluding four such routes instead of proving them. A route whose URI is
 * also matched by an earlier route never runs at all: `/documents/upload`
 * registered after `Route::resource('documents')` is answered by
 * `documents/{document}` with `document = "upload"`, which is a 404 for a page
 * that exists.
 *
 * Both are properties of the route table, so they are checked here against the
 * real table rather than route by route by hand.
 */
class RouteIntegrityTest extends TestCase
{
    public function test_no_route_points_at_a_missing_controller_method(): void
    {
        $missing = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $action = $route->getAction('uses');

            if (! is_string($action) || ! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            if (! class_exists($controller) || ! method_exists($controller, $method)) {
                $missing[] = sprintf(
                    '  %s %s -> %s (%s)',
                    implode('|', array_diff($route->methods(), ['HEAD'])),
                    $route->uri(),
                    $route->getName() ?? 'unnamed',
                    class_basename($controller).'@'.$method,
                );
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These routes name controller methods that do not exist:\n".implode("\n", $missing),
        );
    }

    public function test_every_named_route_matches_itself_before_any_other_route(): void
    {
        $shadowed = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if ($route->getName() === null || $route->getDomain() !== null) {
                continue;
            }

            $path = $this->samplePath($route);

            if ($path === null) {
                continue;
            }

            $matched = RouteFacade::getRoutes()->match(
                \Illuminate\Http\Request::create($path, $this->sampleMethod($route)),
            );

            if ($matched->getName() !== $route->getName()) {
                $shadowed[] = sprintf(
                    '  %s %s -> %s is answered by %s',
                    $this->sampleMethod($route),
                    $route->uri(),
                    $route->getName(),
                    $matched->getName() ?? 'an unnamed route',
                );
            }
        }

        $this->assertSame(
            [],
            $shadowed,
            "These routes are shadowed by a route registered before them:\n".implode("\n", $shadowed),
        );
    }

    /**
     * A concrete path for the route's URI, or null when it cannot be sampled
     * (optional segments, wildcards with unusual shapes).
     */
    private function samplePath(Route $route): ?string
    {
        if (str_contains($route->uri(), '{') === false) {
            return '/'.ltrim($route->uri(), '/');
        }

        $path = preg_replace_callback(
            '/\{([a-z_]+)\??\}/i',
            static function (array $matches): string {
                return str_contains($matches[0], '?') ? '' : '1';
            },
            $route->uri(),
        );

        if ($path === null) {
            return null;
        }

        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function sampleMethod(Route $route): string
    {
        $methods = array_values(array_diff($route->methods(), ['HEAD']));

        return $methods[0] ?? 'GET';
    }
}
