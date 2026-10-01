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
            "default-src 'self'; script-src 'self' 'nonce-{$nonce}' {$viteOrigin}; style-src 'self' 'unsafe-inline' {$viteOrigin} https://fonts.bunny.net; img-src 'self' data: https:; font-src 'self' https: data: {$viteOrigin} https://fonts.bunny.net; connect-src 'self' {$viteOrigin} ws://localhost:".parse_url($viteOrigin, PHP_URL_PORT),
            $csp
        );

        $this->assertStringNotContainsString('[::1]', $csp);
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
