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

        $viteOrigin = trim((string) file_get_contents(public_path('hot')));

        $this->assertSame(
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteOrigin}; style-src 'self' 'unsafe-inline' {$viteOrigin} https://fonts.bunny.net; img-src 'self' data: https:; font-src 'self' https: {$viteOrigin} https://fonts.bunny.net; connect-src 'self' {$viteOrigin} ws://localhost:" . parse_url($viteOrigin, PHP_URL_PORT),
            $response->headers->get('Content-Security-Policy')
        );

        $this->assertStringNotContainsString('[::1]', $response->headers->get('Content-Security-Policy'));
    }
}
