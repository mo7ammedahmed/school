<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Models\Material;
use App\Domain\Schools\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turn a finished live recording into a subject material.
 *
 * This is the app's first queued job, and it runs on the idle `queue` container
 * the compose stack already provides. It is deliberately forgiving: a recording
 * that has not been flushed yet is retried, a missing ffmpeg degrades to the
 * original file, and only an empty recording directory after every retry marks
 * the session `failed` — a lesson nobody can watch should be visible as such in
 * the teacher's list, not silently pending forever.
 *
 * The recording arrives on the `recordings` scratch disk (MediaMTX's volume).
 * The finished file is copied to the private `local` disk — where every other
 * material lives — and the scratch copy is removed, so the stream endpoint only
 * ever has one disk to guard.
 *
 * Jobs run with no tenant context, so the context is pinned from the session
 * itself before anything school-owned is written.
 */
class FinalizeLiveSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public function __construct(public int $liveSessionId) {}

    public function handle(): void
    {
        $session = LiveSession::withoutSchoolScope()->find($this->liveSessionId);

        if ($session === null || $session->recording_status === LiveSession::RECORDING_READY) {
            return;
        }

        app(TenantContext::class)->set((int) $session->school_id);

        $recordings = Storage::disk((string) config('media.disk'));

        $path = collect($recordings->files($session->stream_key))
            ->filter(fn (string $file): bool => Str::endsWith(strtolower($file), ['.mp4', '.mkv', '.ts']))
            ->sortByDesc(fn (string $file): int => $recordings->lastModified($file))
            ->first();

        if ($path === null) {
            if ($this->attempts() < $this->tries) {
                $this->retryLater(20);

                return;
            }

            $session->update(['recording_status' => LiveSession::RECORDING_FAILED]);

            return;
        }

        $size = (int) $recordings->size($path);

        // The hook fires when the publisher disconnects, but the muxer may
        // still be flushing. One stable size across two attempts is the proof
        // that the file is finished.
        if ((int) $session->recording_size !== $size) {
            $session->update([
                'recording_status' => LiveSession::RECORDING_PROCESSING,
                'recording_size' => $size,
            ]);

            $this->retryLater(20);

            return;
        }

        $absolute = $this->localAbsolutePath($recordings, $path);
        $remuxed = $absolute === null ? null : $this->remux($absolute);

        $source = $remuxed ?? $absolute;
        $extension = $source === null ? 'mp4' : (pathinfo($source, PATHINFO_EXTENSION) ?: 'mp4');

        $target = 'materials/recordings/'.$session->school_id.'/'.$session->id.'-'.Str::random(8).'.'.$extension;

        $stream = $source === null
            ? $recordings->readStream($path)
            : fopen($source, 'rb');

        if (! is_resource($stream)) {
            $session->update(['recording_status' => LiveSession::RECORDING_FAILED]);

            return;
        }

        Storage::disk('local')->writeStream($target, $stream);

        fclose($stream);

        $duration = $this->durationOf(Storage::disk('local')->path($target));

        $material = Material::create([
            'school_id' => $session->school_id,
            'offering_id' => $session->offering_id,
            'title' => $session->title,
            'title_ar' => $session->title_ar,
            'description' => 'Recorded live session.',
            'file_path' => $target,
            'file_type' => $extension,
            'file_size' => (int) Storage::disk('local')->size($target),
            'kind' => 'recording',
            'duration_seconds' => $duration,
            'source_live_session_id' => $session->id,
            'is_published' => true,
        ]);

        // The scratch copies have served their purpose: the lesson lives on the
        // private disk now.
        $recordings->delete($path);

        if ($remuxed !== null && $absolute !== null && $remuxed !== $absolute) {
            @unlink($absolute);
        }

        $session->update([
            'recording_status' => LiveSession::RECORDING_READY,
            'recording_path' => $target,
            'material_id' => $material->id,
            'duration_seconds' => $duration ?? $session->duration_seconds,
        ]);
    }

    /**
     * Send the job back to the queue, when there is a queue to return to.
     *
     * `release()` needs the queue to hand the job to; when the job is run
     * in-process (a test, `dispatchSync`) there is none, and the recording is
     * simply left pending for the next sweeper run instead of failing.
     */
    private function retryLater(int $seconds): void
    {
        if ($this->job !== null) {
            $this->release($seconds);
        }
    }

    /**
     * The real path behind a stored file, only when the disk is local.
     *
     * ffmpeg needs a filesystem path, and a remote disk has none. A remote
     * recordings disk skips the remux and keeps the original file.
     */
    private function localAbsolutePath(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $path): ?string
    {
        $driver = config('filesystems.disks.'.config('media.disk').'.driver');

        return $driver === 'local' ? $disk->path($path) : null;
    }

    /**
     * Remux to a faststart MP4 so browsers can seek without downloading it all.
     *
     * `-c copy` rewrites the container only; no re-encode, so it is quick even
     * for an hour-long lesson. Returns the new path, or null when ffmpeg is
     * missing or the remux failed — the caller falls back to the original file.
     */
    private function remux(string $absolute): ?string
    {
        $target = $absolute.'.remux.mp4';

        $result = Process::timeout(1800)->run([
            (string) config('media.ffmpeg'),
            '-v', 'error',
            '-y',
            '-i', $absolute,
            '-c', 'copy',
            '-movflags', '+faststart',
            $target,
        ]);

        if ($result->failed() || ! is_file($target) || filesize($target) === 0) {
            @unlink($target);

            return null;
        }

        return $target;
    }

    /**
     * Duration in whole seconds, or null when ffprobe cannot say.
     */
    private function durationOf(string $absolute): ?int
    {
        if (! is_file($absolute)) {
            return null;
        }

        $result = Process::timeout(60)->run([
            (string) config('media.ffprobe'),
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $absolute,
        ]);

        if ($result->failed()) {
            return null;
        }

        $seconds = (float) trim($result->output());

        return $seconds > 0 ? (int) round($seconds) : null;
    }
}
