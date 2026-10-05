<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_local_csp_allows_the_active_vite_dev_server_origin(): void
    {
        config()->set('app.env', 'local');

        // Pinned to "no media server configured" so this test states the policy
        // for a deployment that watches recordings only; the media origins have
        // their own test below.
        config()->set('media.webrtc_url', null);
        config()->set('media.hls_url', null);

        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => response('ok')
        );

        $hotFile = public_path('hot');
        if (is_file($hotFile)) {
            $viteOrigin = trim((string) file_get_contents($hotFile));
        } else {
            $viteOrigin = 'http://localhost:5173'; // Fallback value from middleware
        }

        $csp = (string) $response->headers->get('Content-Security-Policy');

        // The nonce is per request, so it is read off the header rather than
        // written into this expectation.
        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $nonce = $matches[1] ?? 'NONCE';

        $this->assertSame(
            "default-src 'self'; script-src 'self' 'nonce-{$nonce}' {$viteOrigin}; style-src 'self' 'unsafe-inline' {$viteOrigin} https://fonts.bunny.net; img-src 'self' data: https:; font-src 'self' https: data: {$viteOrigin} https://fonts.bunny.net; connect-src 'self' {$viteOrigin} ws://localhost:".parse_url($viteOrigin, PHP_URL_PORT)."; media-src 'self' blob:",
            $csp
        );

        $this->assertStringNotContainsString('[::1]', $csp);
    }

    public function test_configured_media_origins_are_allowed_for_connect_and_media(): void
    {
        config()->set('app.env', 'local');
        config()->set('media.webrtc_url', 'http://127.0.0.1:8889');
        config()->set('media.hls_url', 'https://media.example.test:8443/');

        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => response('ok')
        );

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('http://127.0.0.1:8889', $this->directive($csp, 'connect-src'));
        $this->assertStringContainsString('https://media.example.test:8443', $this->directive($csp, 'connect-src'));
        $this->assertStringContainsString('http://127.0.0.1:8889', $this->directive($csp, 'media-src'));
        $this->assertStringContainsString('https://media.example.test:8443', $this->directive($csp, 'media-src'));

        // Each endpoint is named once per directive it governs, and a value
        // configured as both endpoints is not repeated within one directive.
        $this->assertSame(1, substr_count($this->directive($csp, 'connect-src'), 'http://127.0.0.1:8889'));
        $this->assertSame(1, substr_count($this->directive($csp, 'media-src'), 'http://127.0.0.1:8889'));
    }

    public function test_a_media_setting_that_is_not_an_origin_contributes_nothing(): void
    {
        config()->set('app.env', 'local');
        config()->set('media.webrtc_url', 'javascript:alert(1)');
        config()->set('media.hls_url', 'not a url');

        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => response('ok')
        );

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('javascript:', $csp);
        $this->assertStringNotContainsString('not a url', $csp);

        // media-src exists but allows only the application's own origin; an
        // unusable setting must not degrade into a broader token.
        $this->assertSame("media-src 'self' blob:", $this->directive($csp, 'media-src'));
    }

    /**
     * `unsafe-inline` and `unsafe-eval` were removed from `script-src`; see
     * ContentSecurityPolicyTest for why, and RenderedPageMatchesItsCspTest for
     * the proof that the rendered document still satisfies the policy.
     */
    public function test_the_dev_origin_survives_the_removal_of_the_unsafe_tokens(): void
    {
        config()->set('app.env', 'local');

        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => response('ok')
        );

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString("'unsafe-inline'", $this->directive($csp, 'script-src'));
        $this->assertStringNotContainsString("'unsafe-eval'", $this->directive($csp, 'script-src'));
        $this->assertStringContainsString('localhost:5173', $this->directive($csp, 'script-src'));
    }

    private function directive(string $csp, string $name): string
    {
        foreach (explode(';', $csp) as $part) {
            $part = trim($part);

            if (str_starts_with($part, $name.' ')) {
                return $part;
            }
        }

        return '';
    }
}
