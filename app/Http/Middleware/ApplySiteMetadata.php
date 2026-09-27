<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Content\Services\SiteMetadata;
use App\Domain\Schools\Services\SchoolResolver;
use Closure;
use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fills <head> for every request: the school's identity first, then the name of
 * the page being viewed.
 *
 * Both live in the *runtime* layer of Laravel Head, which outranks route
 * metadata, so a route that declares its own title is left alone here instead of
 * being overwritten.
 */
class ApplySiteMetadata
{
    public function __construct(
        private readonly SchoolResolver $schools,
        private readonly SiteMetadata $metadata,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user() !== null;

        // A signed-in user without a school selected must not inherit the
        // fallback tenant's branding, which is only there for the public site.
        $school = $this->schools->current(allowFallback: ! $authenticated);

        if ($school !== null) {
            $this->metadata->applyIdentity($school);
        }

        if (! $this->routeDeclaresTitle($request)) {
            $this->metadata->applyRouteName($request->route()?->getName(), $school);
        }

        // The signed-in app is a tool, not a landing page: keep it out of search
        // results. Guests on the public site stay indexable.
        if ($authenticated) {
            Head::robots(['noindex', 'nofollow']);
        }

        return $next($request);
    }

    /**
     * Route metadata is stored in cache-friendly layers under a single `head`
     * key, so any layer carrying a title counts as "the route named its page".
     */
    private function routeDeclaresTitle(Request $request): bool
    {
        $head = $request->route()?->getMetadata('head');

        if (! is_array($head)) {
            return false;
        }

        foreach ($head as $layer) {
            if (is_array($layer) && array_key_exists('title', $layer)) {
                return true;
            }
        }

        return false;
    }
}
