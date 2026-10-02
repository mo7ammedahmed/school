<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Ssr\SsrErrorType;
use Inertia\Ssr\SsrRenderFailed;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports the silent half of an SSR failure.
 *
 * Inertia announces a broken render only when the SSR server answers badly or
 * the request to it throws. Two paths return no response without a word: a
 * missing `bootstrap/ssr/ssr.js` — a build artifact, so a deploy that skips the
 * SSR build looks healthy — and a body that is not JSON. Both quietly leave a
 * page that renders in the browser, which is the failure mode nobody sees.
 *
 * So this asks the question that covers every way of losing SSR at once: did the
 * HTML actually carry server-rendered markup? It has to sit *outside*
 * HandleInertiaRequests for that, because converting the page into HTML is what
 * that middleware does on the way out — any further in, there is nothing to read
 * yet.
 */
class ReportSsrFallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCheck($request, $response) && ! $this->wasServerRendered($response)) {
            SsrRenderFailed::dispatch(
                page: $this->pageData($request, (string) $response->getContent()),
                error: 'Inertia SSR is enabled, but this response carried no server-rendered markup.',
                // The enum names the ways the gateway can fail; this failure is
                // the absence of a render, which it has no case for.
                type: SsrErrorType::Unknown,
                hint: 'The page fell back to rendering in the browser. Check that bootstrap/ssr/ssr.js was built (npm run build) and that the inertia:start-ssr process is running.',
            );
        }

        return $response;
    }

    /**
     * Only a full page load is rendered on the server. An Inertia visit or
     * partial reload answers JSON, a redirect answers nothing to render, and a
     * Blade page (the invoice payer, the error pages) never had SSR to lose.
     */
    private function shouldCheck(Request $request, Response $response): bool
    {
        return (bool) config('inertia.ssr.enabled')
            && $request->isMethod('GET')
            && ! $request->attributes->get('ssr.failure_reported', false)
            && $response->getStatusCode() === 200
            && is_string($response->getContent())
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }

    /**
     * The mount point is Inertia's promise that this document is an Inertia page;
     * the flag is the promise that the server rendered it.
     */
    private function wasServerRendered(Response $response): bool
    {
        $html = (string) $response->getContent();

        return ! str_contains($html, 'id="app"') || str_contains($html, 'data-server-rendered');
    }

    /**
     * What the page will mount, read from the payload Inertia writes into the
     * document. Only the two identifying keys are taken: the props are the
     * visitor's own data and have no business in a log line.
     *
     * @return array{component: string, url: string}
     */
    private function pageData(Request $request, string $html): array
    {
        if (! preg_match('/<script data-page="app"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            return ['component' => 'Unknown', 'url' => $request->fullUrl()];
        }

        $page = json_decode($matches[1], true);

        if (! is_array($page)) {
            return ['component' => 'Unknown', 'url' => $request->fullUrl()];
        }

        return [
            'component' => is_string($page['component'] ?? null) ? $page['component'] : 'Unknown',
            'url' => is_string($page['url'] ?? null) ? $page['url'] : $request->fullUrl(),
        ];
    }
}
