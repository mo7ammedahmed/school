<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Services\MediaMtxClient;
use App\Domain\Schools\Support\TenantContext;
use App\Jobs\FinalizeLiveSession;
use Illuminate\Console\Command;

/**
 * Reconcile live sessions the app lost track of.
 *
 * Two things can leave a session stranded: a teacher whose browser closed
 * without pressing “End session”, and a recording job that never ran because
 * the process that queued it died. This sweeper ends sessions whose publisher
 * is gone, and re-dispatches the finalize job for every ended session whose
 * recording is still pending. Both paths are idempotent — the job checks its
 * own state before doing anything.
 *
 * "The publisher is gone" is decided by asking the live server, not by guessing
 * from a clock: a lesson can legitimately run long, and ending one because it
 * passed an arbitrary age would cut a teacher off mid-sentence. The four-hour
 * bound is the backstop for when the live server itself cannot be reached — a
 * session that old with no answer from the server is not a live lesson any
 * more, whatever the reason.
 *
 * It runs from the scheduler container the compose stack already starts.
 */
class FinalizeLiveSessions extends Command
{
    protected $signature = 'live-sessions:finalize';

    protected $description = 'End stale live sessions and finalize pending recordings';

    public function handle(MediaMtxClient $client): int
    {
        $ended = 0;

        $candidates = LiveSession::withoutSchoolScope()
            ->where('status', LiveSession::STATUS_LIVE)
            ->get();

        foreach ($candidates as $session) {
            $publishing = $client->isPublishing($session->stream_key);

            $staleByClock = $session->started_at !== null && $session->started_at->lt(now()->subHours(4));

            // `false` is the server saying the publisher left; `null` means it
            // could not be asked, and only the backstop may end that session.
            if ($publishing !== false && ! ($publishing === null && $staleByClock)) {
                continue;
            }

            app(TenantContext::class)->set((int) $session->school_id);

            $endedAt = now();

            $session->update([
                'status' => LiveSession::STATUS_ENDED,
                'ended_at' => $endedAt,
                'duration_seconds' => $session->started_at !== null
                    ? (int) $session->started_at->diffInSeconds($endedAt)
                    : 0,
            ]);

            FinalizeLiveSession::dispatch($session->id);
            $ended++;
        }

        $awaiting = LiveSession::withoutSchoolScope()->awaitingRecording()->get();

        foreach ($awaiting as $session) {
            FinalizeLiveSession::dispatch($session->id);
        }

        $this->info(sprintf(
            'Ended %d stale session(s); queued %d recording(s) for finalization.',
            $ended,
            $awaiting->count(),
        ));

        return self::SUCCESS;
    }
}
