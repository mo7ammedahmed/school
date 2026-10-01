<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The Content-Security-Policy is only as strong as its weakest token, and
 * `script-src 'self' 'unsafe-inline' 'unsafe-eval'` has no strength: it permits
 * executing any string any page in the app can reach, which is exactly what a
 * cross-site scripting payload needs. Every other defence here — the CSRF
 * token, the tenant scope, the policy layer — is bypassed by script that runs
 * before any of them are consulted.
 *
 * Both tokens are also *redundant* rather than merely weak, which is the part
 * that makes removing them safe:
 *
 *  - `unsafe-inline` exists so the Inertia bootstrap and the Vite tags can carry
 *    inline script. A per-request nonce expresses the same intent and is
 *    strictly narrower, because it is known only to the one response that
 *    contains the script it authorises.
 *  - `unsafe-eval` is needed by the dev server's HMR client and nothing in a
 *    production build. Laravel's `Vite::useCspNonce()` propagates the nonce to
 *    every generated tag, including the React refresh preamble.
 *
 * `X-XSS-Protection` goes at the same time: it was removed from every modern
 * browser years ago, and leaving it set is worse than leaving it out — it
 * advertises a protection that is no longer there.
 */
class ContentSecurityPolicyTest extends TestCase
{
    private function csp(string $env = 'production'): string
    {
        config()->set('app.env', $env);

        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => response('ok'),
        );

        $header = $response->headers->get('Content-Security-Policy');

        $this->assertIsString($header, 'The middleware set no Content-Security-Policy at all.');

        return $header;
    }

    public function test_script_src_does_not_permit_inline_script(): void
    {
        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $this->directive($this->csp(), 'script-src'),
            "script-src still permits inline script, so an injected <script> body executes.\n"
            .$this->csp(),
        );
    }

    public function test_script_src_does_not_permit_eval(): void
    {
        $this->assertStringNotContainsString(
            "'unsafe-eval'",
            $this->directive($this->csp(), 'script-src'),
            "script-src still permits eval(), so injected script can construct code at runtime.\n"
            .$this->csp(),
        );
    }

    public function test_script_src_is_non_trivially_restricted(): void
    {
        $tokens = $this->tokens($this->directive($this->csp(), 'script-src'));

        $this->assertContains(
            "'self'",
            $tokens,
            'script-src dropped \'self\' along with the unsafe tokens, so the policy now blocks the '
            .'application\'s own bundles as well as the payloads it was meant to block.',
        );
    }

    /**
     * A nonce is only useful if it is unpredictable and per-response. A constant,
     * or one reused across requests, gives an attacker who has seen one page
     * everything they need for every page.
     */
    public function test_each_response_gets_its_own_nonce(): void
    {
        config()->set('app.env', 'production');

        $seen = [];

        for ($i = 0; $i < 5; $i++) {
            $response = (new SecurityHeaders)->handle(
                Request::create('/'),
                fn () => response('ok'),
            );

            $seen[] = $this->nonceFrom($response->headers->get('Content-Security-Policy'));
        }

        $this->assertCount(5, array_unique($seen), 'The CSP nonce repeated across requests, so it is not per-response.');

        foreach ($seen as $nonce) {
            $this->assertNotEmpty($nonce, 'A response carried no nonce, so its inline script is refused outright.');
        }
    }

    /**
     * The nonce must be unguessable. A short or low-entropy value is a constant
     * with extra steps.
     */
    public function test_the_nonce_is_long_and_random(): void
    {
        config()->set('app.env', 'production');

        $response = (new SecurityHeaders)->handle(Request::create('/'), fn () => response('ok'));
        $nonce = $this->nonceFrom($response->headers->get('Content-Security-Policy'));

        $this->assertGreaterThanOrEqual(
            32,
            strlen($nonce),
            "The CSP nonce is {$nonce}. Anything under 32 characters of base64 entropy is enumerable.",
        );

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=_-]+$/', $nonce, 'The nonce must be base64.');
    }

    public function test_local_csp_still_permits_the_dev_server_to_load_its_bundle(): void
    {
        $csp = $this->csp('local');

        // Dropping `unsafe-eval` must not break development. The HMR client
        // needs the dev origin, and a developer who cannot load the app cannot
        // verify any of this.
        $this->assertStringContainsString('localhost', $this->directive($csp, 'script-src'), $csp);
    }

    public function test_the_deprecated_x_xss_protection_header_is_gone(): void
    {
        config()->set('app.env', 'production');

        $response = (new SecurityHeaders)->handle(Request::create('/'), fn () => response('ok'));

        $this->assertFalse(
            $response->headers->has('X-XSS-Protection'),
            'X-XSS-Protection is set. Every current browser ignores it, so it advertises a protection '
            .'the application does not have.',
        );
    }

    /**
     * The rest of the header is load-bearing; a policy tightened too far is a
     * denial of service rather than a hardening.
     */
    public function test_the_other_defences_are_untouched(): void
    {
        $csp = $this->csp();

        foreach (['default-src', 'style-src', 'img-src', 'font-src', 'connect-src'] as $directive) {
            $this->assertNotSame(
                '',
                $this->directive($csp, $directive),
                "{$directive} was dropped from the policy, which blocks more than the change intended.",
            );
        }

        // React and the UI set element styles at runtime, so style-src keeps its
        // inline allowance. That is a materially weaker position than script-src
        // and is recorded as a follow-up rather than silently treated as done.
        $this->assertStringContainsString("'self'", $this->directive($csp, 'style-src'));
    }

    // ------------------------------------------------------------------

    private function directive(string $csp, string $name): string
    {
        foreach (explode(';', $csp) as $part) {
            $part = trim($part);

            if (str_starts_with($part, $name.' ') || str_starts_with($part, $name.'*')) {
                return $part;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function tokens(string $directive): array
    {
        $parts = preg_split('/\s+/', trim($directive)) ?: [];

        return array_values(array_filter(array_slice($parts, 1)));
    }

    private function nonceFrom(?string $csp): string
    {
        $this->assertIsString($csp, 'The middleware set no Content-Security-Policy.');

        if (preg_match("/'nonce-([^']+)'/", $csp, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
