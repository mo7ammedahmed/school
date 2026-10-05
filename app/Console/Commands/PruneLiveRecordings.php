<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Learning\Models\LiveSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Delete the scratch recordings nobody is going to read.
 *
 * MediaMTX writes every published stream to the `recordings` disk, and the
 * finalize job concatenates a session's segments and moves the single lesson
 * onto the private disk. What stays behind is real but useless: the files of a
 * session that failed, a job that died mid-copy, or a stream the app never
 * tracked at all. Nothing serves them; they only grow.
 *
 * The rule has two halves, and the difference matters:
 *   * under a stream key whose session is already `ready` or `failed` there is
 *     no consumer left — the file goes now, however young it is;
 *   * anywhere else, a file older than the retention window is stale by
 *     definition: the finalize job retries for minutes, not days, so a
 *     recording that old will never be picked up.
 *
 * A file being written right now is younger than any sane window, so a live
 * lesson is never touched.
 */
class PruneLiveRecordings extends Command
{
    protected $signature = 'live-sessions:prune {--hours= : Override the retention window, in hours}';

    protected $description = 'Delete scratch recordings whose lesson is published, and files older than the retention window';

    public function handle(): int
    {
        $disk = Storage::disk((string) config('media.disk'));

        $hours = max(0, (int) ($this->option('hours') ?? config('media.recordings_retention_hours', 24)));
        $cutoff = now()->subHours($hours)->getTimestamp();

        $publishedKeys = LiveSession::withoutSchoolScope()
            ->whereIn('recording_status', [LiveSession::RECORDING_READY, LiveSession::RECORDING_FAILED])
            ->pluck('stream_key')
            ->all();

        $deleted = 0;
        $freed = 0;

        foreach ($disk->allFiles() as $file) {
            $published = in_array(Str::before($file, '/'), $publishedKeys, true);

            if (! $published && $disk->lastModified($file) >= $cutoff) {
                continue;
            }

            $freed += (int) $disk->size($file);
            $disk->delete($file);
            $deleted++;
        }

        $directories = 0;

        foreach ($disk->directories() as $directory) {
            if ($disk->files($directory) === []) {
                $disk->deleteDirectory($directory);
                $directories++;
            }
        }

        $this->info(sprintf(
            'Deleted %d recording file(s) (%d bytes) and %d empty director(ies).',
            $deleted,
            $freed,
            $directories,
        ));

        return self::SUCCESS;
    }
}
