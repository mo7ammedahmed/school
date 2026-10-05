<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Services\LiveMediaAccess;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/** Session-protected HLS also works in browsers whose native player cannot send bearer headers. */
final class LiveHlsController extends Controller
{
    public function __invoke(Request $request, LiveSession $liveSession, string $file, LiveMediaAccess $access): Response
    {
        abort_unless($access->allows($request->user(), $liveSession, 'read'), 403);
        abort_unless(preg_match('/\A[A-Za-z0-9_-]+\.(?:m3u8|mp4|m4s|ts)\z/', $file) === 1, 404);
        $base = rtrim((string) (config('media.hls_internal_url') ?: config('media.hls_url')), '/');
        abort_if($base === '', 503);

        $query = $request->validate([
            '_HLS_msn' => ['sometimes', 'integer', 'min:0'],
            '_HLS_part' => ['sometimes', 'integer', 'min:0'],
            '_HLS_skip' => ['sometimes', 'in:YES,v2'],
        ]);
        try {
            $upstream = Http::timeout(15)->withoutRedirecting()
                ->withToken($access->issue($request->user(), $liveSession, 'read'))
                ->get($base.'/'.$liveSession->stream_key.'/'.$file, $query);
        } catch (ConnectionException) {
            abort(503, 'Live video is temporarily unavailable.');
        }
        abort_unless($upstream->successful(), $upstream->status() === 404 ? 404 : 502);

        $body = $upstream->body();
        if (str_ends_with($file, '.m3u8')) {
            // Every playlist reference must remain relative to this protected route.
            foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
                $references = [];
                if ($line !== '' && ! str_starts_with($line, '#')) {
                    $references[] = $line;
                }
                preg_match_all('/URI="([^"]+)"/', $line, $matches);
                $references = array_merge($references, $matches[1]);
                foreach ($references as $reference) {
                    abort_unless(preg_match('/\A[A-Za-z0-9_-]+\.(?:m3u8|mp4|m4s|ts)(?:\?[^#\s]*)?\z/', $reference) === 1, 502);
                }
            }
        }

        return response($body, 200, [
            'Content-Type' => str_ends_with($file, '.m3u8') ? 'application/vnd.apple.mpegurl'
                : (str_ends_with($file, '.ts') ? 'video/mp2t' : 'video/mp4'),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
