<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Models\Material;
use App\Domain\Schools\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
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
 * A lesson that ran past one MediaMTX record-segment arrives as several files;
 * they are concatenated into one before anything is published, because a lesson
 * that quietly contains only its last hour is worse than a lesson marked
 * failed.
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

        $segments = $this->segments($recordings, $session->stream_key);

        if ($segments->isEmpty()) {
            if ($this->attempts() < $this->tries) {
                $this->retryLater(20);

                return;
            }

            $session->update(['recording_status' => LiveSession::RECORDING_FAILED]);

            return;
        }

        $newest = (string) $segments->last();
        $newestExtension = pathinfo($newest, PATHINFO_EXTENSION) ?: 'mp4';

        // The total, not the newest file alone: MediaMTX opens a new segment
        // when a lesson runs past `recordSegmentDuration`, so a new file
        // changes the sum. One stable total across two attempts is the proof
        // that the recorder has stopped writing.
        $size = $segments->sum(fn (string $file): int => (int) $recordings->size($file));

        if ((int) $session->recording_size !== $size) {
            $session->update([
                'recording_status' => LiveSession::RECORDING_PROCESSING,
                'recording_size' => $size,
            ]);

            $this->retryLater(20);

            return;
        }

        $absolute = $this->localAbsolutePath($recordings, $newest);

        // A lesson written in several segments is concatenated into one and
        // never published as its last segment alone. Concatenation is skipped
        // when it cannot succeed — no ffmpeg, a remote recordings disk, a codec
        // change mid-lesson — and the newest segment is published rather than
        // failing the whole lesson.
        $concatenated = $absolute === null || $segments->count() === 1
            ? null
            : $this->concatenate($recordings, $segments);

        $remuxed = $concatenated === null && $absolute !== null ? $this->remux($absolute) : null;

        $source = $concatenated ?? $remuxed ?? $absolute;
        $extension = $concatenated !== null || $remuxed !== null ? 'mp4' : $newestExtension;

        $target = 'materials/recordings/'.$session->school_id.'/'.$session->id.'-'.Str::random(8).'.'.$extension;

        $stream = $source === null
            ? $recordings->readStream($newest)
            : fopen($source, 'rb');

        if (! is_resource($stream)) {
            $session->update(['recording_status' => LiveSession::RECORDING_FAILED]);

            return;
        }

        // The private disk, by the name the deployment gave it: `local` on a
        // machine whose storage persists, a bucket on Laravel Cloud, where the
        // filesystem is ephemeral and a lesson written to it would be gone by
        // the next deploy. The stream route reads the same name.
        $private = Storage::disk($this->privateDisk());

        $private->writeStream($target, $stream);

        fclose($stream);

        // The duration comes from the file ffmpeg wrote, before it crosses to
        // the private disk. `path()` exists only on a local disk, and the target
        // may be a bucket — the local read is what keeps durations working when
        // it is. A remote-to-remote job (no local artifact at all) leaves the
        // duration unknown rather than guessing it.
        $duration = $source === null ? null : $this->durationOf($source);

        $material = Material::create([
            'school_id' => $session->school_id,
            'offering_id' => $session->offering_id,
            'title' => $session->title,
            'title_ar' => $session->title_ar,
            'description' => 'Recorded live session.',
            'file_path' => $target,
            'file_type' => $extension,
            'file_size' => (int) $private->size($target),
            'kind' => 'recording',
            'duration_seconds' => $duration,
            'source_live_session_id' => $session->id,
            'is_published' => true,
        ]);

        // The scratch copies have served their purpose: the lesson lives on the
        // private disk now. Every segment goes, not only the one that was
        // copied, and so do the files ffmpeg wrote beside them — anything left
        // there is a full duplicate of the lesson waiting to fill the volume.
        foreach ($segments as $segment) {
            $recordings->delete($segment);
        }

        foreach ([$concatenated, $remuxed] as $artifact) {
            if ($artifact !== null) {
                @unlink($artifact);
            }
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
     * The disk a finished lesson is published to.
     *
     * Named in one place — `filesystems.private` — because three other readers
     * open the file afterwards: the download route, the stream route, and the
     * prune command's idea of where a lesson lives.
     */
    private function privateDisk(): string
    {
        return (string) config('filesystems.private', 'local');
    }

    /**
     * The real path behind a stored file, only when the disk is local.
     *
     * ffmpeg needs a filesystem path, and a remote disk has none. A remote
     * recordings disk skips the remux and keeps the original file.
     */
    private function localAbsolutePath(Filesystem $disk, string $path): ?string
    {
        $driver = config('filesystems.disks.'.config('media.disk').'.driver');

        return $driver === 'local' ? $disk->path($path) : null;
    }

    /**
     * Every finished recording of a session, oldest first.
     *
     * MediaMTX segments a lesson that runs past `recordSegmentDuration` into
     * several files inside the session's directory, and each name carries the
     * moment its segment started. Sorting by name is therefore sorting by time,
     * and unlike the modification time it cannot be rewritten by a copy or a
     * backup tool. Files this job writes itself (`.remux.mp4`, the concatenation
     * list) are not recordings and are never listed.
     *
     * @return Collection<int, string>
     */
    private function segments(Filesystem $disk, string $streamKey): Collection
    {
        return collect($disk->files($streamKey))
            ->filter(fn (string $file): bool => Str::endsWith(strtolower($file), ['.mp4', '.mkv', '.ts']))
            ->reject(fn (string $file): bool => str_contains($file, '.remux.') || str_contains($file, '.concat.'))
            ->sort()
            ->values();
    }

    /**
     * Concatenate every segment into one faststart MP4, or null when that is
     * not possible.
     *
     * The concat demuxer reads a list written beside the recordings; `-c copy`
     * only rewrites the container, so even an hour of lesson takes seconds. The
     * output is named `.concat.tmp` so a crash mid-write cannot leave a file
     * that the next finalize run mistakes for a segment.
     *
     * @param  Collection<int, string>  $segments
     */
    private function concatenate(Filesystem $disk, Collection $segments): ?string
    {
        $directory = dirname($disk->path((string) $segments->last()));
        $list = $directory.'/concat.txt';
        $target = $directory.'/concat.tmp';

        $entries = $segments
            ->map(fn (string $file): string => "file '".str_replace(DIRECTORY_SEPARATOR, '/', $disk->path($file))."'")
            ->implode(PHP_EOL);

        if (@file_put_contents($list, $entries.PHP_EOL) === false) {
            return null;
        }

        $result = Process::timeout(1800)->run([
            (string) config('media.ffmpeg'),
            '-v', 'error',
            '-y',
            '-f', 'concat',
            '-safe', '0',
            '-i', $list,
            '-c', 'copy',
            '-movflags', '+faststart',
            '-f', 'mp4',
            $target,
        ]);

        @unlink($list);

        if ($result->failed() || ! is_file($target) || filesize($target) === 0) {
            @unlink($target);

            return null;
        }

        return $target;
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
