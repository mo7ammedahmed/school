<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Does a rendered page actually satisfy the policy this application now sends?
 *
 * A CSP that is correct as a string and broken in a browser is worse than a weak
 * one, because it is trusted. The only way to know the document and the header
 * agree is to render the document and read the header off the same response, then
 * check that every executable inline script it contains carries a nonce the
 * header lists.
 */
class RenderedPageMatchesItsCspTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_inline_script_on_a_rendered_page_carries_the_header_nonce(): void
    {
        School::factory()->create();

        $response = $this->get('/');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertNotSame('', $csp, 'No CSP was sent with the page.');

        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $nonce = $matches[1] ?? '';
        $this->assertNotSame('', $nonce, "The policy advertises no nonce:\n{$csp}");

        $html = $response->getContent();

        $this->assertIsString($html);

        preg_match_all('/<script\b([^>]*)>/i', $html, $found);

        $offenders = [];

        foreach ($found[1] as $attributes) {
            // A `<script>` carrying a src is not inline; a `type` that is not
            // JavaScript is a data block, not code, and CSP's script-src does
            // not govern it. Inertia's page payload is `application/json`.
            if (preg_match('/\bsrc\s*=/i', $attributes) === 1) {
                continue;
            }

            if (preg_match('/\btype\s*=\s*["\']?([\w-]+)/i', $attributes, $type) === 1
                && ! in_array(strtolower($type[1]), ['module', 'text/javascript', 'application/javascript'], true)) {
                continue;
            }

            if (str_contains($attributes, 'nonce="'.$nonce.'"') || str_contains($attributes, "nonce='".$nonce."'")) {
                continue;
            }

            $offenders[] = trim($attributes);
        }

        $this->assertSame(
            [],
            $offenders,
            'The page carries executable inline script the CSP does not authorise. The browser blocks it '
            ."and the application is broken in a way no unit test on the header would have shown:\n"
            .implode("\n", $offenders),
        );
    }

    /**
     * The nonce is per response, so it must not leak into a cached page: a shared
     * cache serving one client's nonce to another breaks the second page, and
     * reusing a nonce across users is the failure the per-response test exists
     * to prevent.
     */
    public function test_the_page_is_not_served_from_a_cache_that_could_freeze_the_nonce(): void
    {
        School::factory()->create();

        $response = $this->get('/');

        $this->assertStringNotContainsString(
            'public',
            strtolower((string) $response->headers->get('Cache-Control', '')),
            'The response is publicly cacheable while carrying a per-response nonce, so a shared cache '
            .'would hand one visitor the nonce that authorises another visitor\'s script.',
        );
    }
}
