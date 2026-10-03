<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\Schools\Support\TenantContext;
use App\Jobs\FinalizeLiveSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Where MediaMTX tells the app that a publisher disconnected.
 *
 * The hook carries no user session — it is the live server talking to the app —
 * so it authenticates with a shared secret header instead. An unset secret
 * refuses every call: the endpoint is reachable from the network and must never
 * be open by accident.
 *
 * The handler is deliberately idempotent. The teacher may also have pressed
 * “End session”, and the scheduled sweeper may be running; ending an already
 * ended session is a no-op and the finalize job checks its own state.
 */
class MediaHookController extends Controller
{
    public function notReady(Request $request): JsonResponse
    {
        $secret = (string) config('media.hook_secret');
        $provided = (string) $request->header('X-Media-Secret', '');

        abort_if($secret === '' || ! hash_equals($secret, $provided), 403);

        $key = trim((string) $request->query('path', ''));

        if ($key === '') {
            return response()->json(['ignored' => 'no path']);
        }

        $session = LiveSession::withoutSchoolScope()
            ->where('stream_key', $key)
            ->first();

        if ($session === null) {
            return response()->json(['ignored' => 'unknown path']);
        }

        app(TenantContext::class)->set((int) $session->school_id);

        if ($session->status === LiveSession::STATUS_LIVE) {
            $endedAt = now();

            $session->update([
                'status' => LiveSession::STATUS_ENDED,
                'ended_at' => $endedAt,
                'duration_seconds' => $session->started_at !== null
                    ? (int) $session->started_at->diffInSeconds($endedAt)
                    : 0,
            ]);
        }

        FinalizeLiveSession::dispatch($session->id);

        return response()->json(['ok' => true]);
    }
}
