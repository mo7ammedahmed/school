<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $isLocal = config('app.env') === 'local';
        $viteOrigin = $isLocal ? ' ' . $this->viteOrigin() : '';
        $fontOrigin = "https://fonts.bunny.net";

        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval'" . $viteOrigin;
        $styleSrc = "'self' 'unsafe-inline'" . $viteOrigin . " $fontOrigin";
        $fontSrc = "'self' https:" . $viteOrigin . " $fontOrigin";
        $viteWebSocketOrigin = $isLocal ? ' ws://localhost:' . $this->vitePort() : '';
        $connectSrc = "'self'" . $viteOrigin . $viteWebSocketOrigin;

        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src $scriptSrc; style-src $styleSrc; img-src 'self' data: https:; font-src $fontSrc; connect-src $connectSrc");

        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function viteOrigin(): string
    {
        $fallback = 'http://localhost:5173';
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return $fallback;
        }

        $hotUrl = trim((string) file_get_contents($hotFile));
        $parts = parse_url($hotUrl);

        if (
            ! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || ! in_array($parts['host'] ?? null, ['localhost', '127.0.0.1'], true)
            || isset($parts['user'], $parts['pass'], $parts['path'], $parts['query'], $parts['fragment'])
            || ! isset($parts['port'])
        ) {
            return $fallback;
        }

        return $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
    }

    private function vitePort(): int
    {
        $origin = $this->viteOrigin();
        $port = parse_url($origin, PHP_URL_PORT);

        return is_int($port) ? $port : 5173;
    }
}
