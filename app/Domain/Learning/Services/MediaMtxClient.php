<?php

declare(strict_types=1);

namespace App\Domain\Learning\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Ask the live server whether a stream is still being published.
 *
 * MediaMTX exposes a small HTTP API next to the media ports. The app uses it to
 * reconcile sessions the teacher never ended: a browser that closed, a laptop
 * that slept. The alternative — MediaMTX calling *us* through `runOnNotReady` —
 * needs an HTTP client inside the MediaMTX image, and the official image ships
 * none; the API works with what is actually installed.
 *
 * Every answer is three-valued on purpose. `true`/`false` are the server's
 * answer; `null` means "cannot tell" (not configured, unreachable, or an
 * unexpected response), and the caller must treat that as "leave it alone"
 * rather than "it stopped" — a monitoring blip must not end a live lesson.
 */
class MediaMtxClient
{
    public function isPublishing(string $streamKey): ?bool
    {
        $base = rtrim((string) config('media.api_url'), '/');

        if ($base === '' || $streamKey === '') {
            return null;
        }

        try {
            $response = Http::timeout(3)->acceptJson()->get($base.'/v3/paths/get/'.$streamKey);
        } catch (Throwable) {
            return null;
        }

        // A path that is not in the configuration at all is a stream nobody is
        // publishing — that is a real "no".
        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            return null;
        }

        $ready = $response->json('ready');

        if (! is_bool($ready)) {
            return null;
        }

        // `ready` is true while a publisher is connected. Readers do not keep
        // it true once the teacher's browser has gone.
        return $ready;
    }
}