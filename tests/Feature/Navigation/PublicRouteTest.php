<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Two routes on one method and URI: the later registration silently wins.
 *
 * That is how the public teaching-staff page disappeared. `Route::get('/teachers')`
 * was declared for the site, and the dashboard's `Route::resource('teachers')`
 * came after it, so the public route was never registered at all — the route
 * table held one `/teachers`, and it belonged to the dashboard. The site's own
 * navigation sent visitors to the login screen instead of to the staff page, and
 * nothing failed loudly enough to notice.
 *
 * Two checks, because either alone would have missed it: a route written in
 * routes/web.php has to be the route that answers for its own URI, and a link the
 * public site renders has to arrive at the route that link names.
 *
 * Only declarations that fit on one line are read here. A route built from a
 * group prefix and a concatenated name is a person writing a route on purpose;
 * this guard is for the ones that read like a single statement and behave like
 * something else.
 */
class PublicRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Public nav entries whose key is a page label rather than a route name.
     *
     * @var list<string>
     */
    private const array NOT_ROUTE_NAMES = ['public.applyNow'];

    public function test_every_route_declared_on_one_line_answers_for_its_own_uri(): void
    {
        $misrouted = [];

        foreach ($this->declaredRoutes() as [$method, $uri, $name]) {
            try {
                $matched = Router::getRoutes()->match(Request::create($uri, $method));
            } catch (NotFoundHttpException) {
                $misrouted[] = "{$method} {$uri} ({$name}) has no route at all";

                continue;
            }

            $matchedName = $matched->getName() ?? $matched->uri();

            if ($matchedName !== $name) {
                $misrouted[] = "{$method} {$uri} is declared as {$name}, and answers as {$matchedName}";
            }
        }

        $this->assertSame(
            [],
            $misrouted,
            "A route another one replaced is a page nobody can reach:\n  ".implode("\n  ", $misrouted),
        );
    }

    public function test_every_public_link_arrives_at_the_route_it_names(): void
    {
        $wrong = [];

        foreach ($this->publicLinks() as [$name, $href]) {
            if (in_array($name, self::NOT_ROUTE_NAMES, true)) {
                continue;
            }

            try {
                $matched = Router::getRoutes()->match(Request::create($href, 'GET'));
            } catch (NotFoundHttpException) {
                $wrong[] = "the navigation links to {$href} ({$name}), and nothing answers there";

                continue;
            }

            $matchedName = $matched->getName() ?? $matched->uri();

            if ($matchedName === $name || str_starts_with($matchedName, $name.'.')) {
                continue;
            }

            $wrong[] = "the navigation links {$href} as {$name}, and reaches {$matchedName}";
        }

        $this->assertSame(
            [],
            $wrong,
            "A link that lands somewhere else is worse than a link that 404s:\n  ".implode("\n  ", $wrong),
        );
    }

    /**
     * Routes written as one statement: method, literal URI, literal name.
     *
     * Only lines that begin at the left margin count. An indented route sits in a
     * group, so its literal URI is missing the group's prefix and cannot be matched
     * as written.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function declaredRoutes(): array
    {
        $routes = [];

        foreach (glob(base_path('routes/*.php')) ?: [] as $file) {
            $lines = explode("\n", (string) file_get_contents($file));

            foreach ($lines as $line) {
                if (! preg_match(
                    "/^Route::(get|post|put|patch|delete)\\(\\s*'([^']+)'[^\\n]*?->name\\(\\s*'([^']+)'\\s*\\)/",
                    $line,
                    $match,
                )) {
                    continue;
                }

                $routes[] = [strtoupper($match[1]), '/'.preg_replace('/\{[^}]+\}/', '1', ltrim($match[2], '/')), $match[3]];
            }
        }

        return $routes;
    }

    /**
     * The site's own navigation: `{ key: 'public.teachers', href: '/faculty' }`.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function publicLinks(): array
    {
        $layout = base_path('resources/js/layouts/public-layout.tsx');

        if (! is_file($layout)) {
            $this->fail('The public layout no longer exists, so its links cannot be checked.');
        }

        preg_match_all(
            "/\\{\\s*key:\\s*'([^']+)'\\s*,\\s*href:\\s*'([^']+)'\\s*\\}/",
            (string) file_get_contents($layout),
            $matches,
            PREG_SET_ORDER,
        );

        return array_map(static fn (array $match): array => [$match[1], $match[2]], $matches);
    }
}
