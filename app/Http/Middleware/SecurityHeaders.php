<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * One Content-Security-Policy per response, built around a per-request nonce.
 *
 * The nonce is the whole point. `script-src 'unsafe-inline'` cannot be
 * distinguished from "any script on this origin may run", which is precisely the
 * capability a cross-site scripting payload needs and precisely what every other
 * control here — CSRF, the tenant scope, the policy layer — is bypassed by.
 * A nonce says the same thing about the scripts this response actually emitted,
 * and nothing more, so the Inertia bootstrap and the Vite tags can stay inline
 * without the policy becoming a no-op.
 *
 * The nonce is generated per response and lives on the request, so the Blade
 * view reads the same value the header advertises. `Vite::useCspNonce()` is
 * called with that exact string: Laravel propagates it to every generated tag,
 * including the React refresh preamble that `@viteReactRefresh` emits.
 *
 * Order note: this middleware must run *before* the response body is rendered,
 * because the body has to carry the nonce. It is appended to the web group, and
 * Inertia renders during the response, so the header is set on the way out with
 * the value that was already placed on the request on the way in.
 */
class SecurityHeaders
{
    public const NONCE_ATTRIBUTE = 'nonce';

    /**
     * The nonce for this request, read back by the Blade view.
     */
    private const REQUEST_ATTRIBUTE = '_csp_nonce';

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->nonceFor($request);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $response->headers->set('Content-Security-Policy', $this->policy($nonce));

        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * The nonce for the current request, generating it on first read.
     *
     * Read by {@see handle()} on the way out and by the Blade view when the body
     * renders. It is one value for both, so a tag can never carry a nonce the
     * header does not list.
     */
    public static function nonce(Request $request): string
    {
        $existing = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $nonce = self::generate();

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $nonce);

        return $nonce;
    }

    public function policy(string $nonce): string
    {
        $isLocal = config('app.env') === 'local';
        $viteOrigin = $isLocal ? ' '.$this->viteOrigin() : '';
        $fontOrigin = 'https://fonts.bunny.net';

        // No 'unsafe-inline' and no 'unsafe-eval'. The nonce authorises the tags
        // this response emits; 'self' covers the built bundle.
        $scriptSrc = "'self' 'nonce-{$nonce}'".$viteOrigin;

        // style-src keeps 'unsafe-inline' and that is a real, deliberate gap:
        // the UI sets element styles at runtime, so removing it needs a nonce or
        // a refactor of every inline style binding. It is recorded as a
        // follow-up rather than quietly treated as hardened. Script is the
        // dangerous one — a style injection cannot read the CSRF token, call
        // the API, or exfiltrate anything.
        $styleSrc = "'self' 'unsafe-inline'".$viteOrigin." $fontOrigin";
        $fontSrc = "'self' https: data:".$viteOrigin." $fontOrigin";
        $viteWebSocketOrigin = $isLocal ? ' ws://localhost:'.$this->vitePort() : '';
        $connectSrc = "'self'".$viteOrigin.$viteWebSocketOrigin;

        return "default-src 'self'; script-src {$scriptSrc}; style-src {$styleSrc}; "
            ."img-src 'self' data: https:; font-src {$fontSrc}; connect-src {$connectSrc}";
    }

    /**
     * 32 bytes of CSPRNG entropy, base64-encoded — 44 characters.
     *
     * The nonce is guessable for exactly as long as its entropy holds, and
     * guessing it defeats the entire policy, so it is drawn from the secure
     * generator rather than a string helper. `Str::random` falls back to
     * `random_bytes` on the platforms this runs on, but the intent is stated
     * explicitly here because the fallback is the whole security property.
     */
    private static function generate(): string
    {
        return base64_encode(random_bytes(32));
    }

    private function nonceFor(Request $request): string
    {
        $nonce = self::nonce($request);

        // Hand the same value to Vite so every tag it renders carries it. Safe to
        // call unconditionally: a second call with the same value is a no-op, and
        // Vite is only consulted when a manifest or hot file is present.
        if (app()->bound('vite')) {
            app('vite')->useCspNonce($nonce);
        }

        return $nonce;
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

        return $parts['scheme'].'://'.$parts['host'].':'.$parts['port'];
    }

    private function vitePort(): int
    {
        $origin = $this->viteOrigin();
        $port = parse_url($origin, PHP_URL_PORT);

        return is_int($port) ? $port : 5173;
    }
}
