<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a stored upload back to the person who uploaded it.
 *
 * Uploads are written to the private disk and nothing in the web root links to
 * them, so without this the feature is write-only: a teacher attaches a lesson
 * plan and there is no way to get it back. This is the one route kind that opens
 * a file by a path held in a database column, which is what makes the guard below
 * the whole point of the trait.
 *
 * Every refusal is a 404 rather than a 403 or a 500. A record with no attachment
 * is an ordinary record, and a stored path that will not be served is not
 * something the caller needs to be able to distinguish from a missing one.
 *
 * The disk is `filesystems.private`, not a hardcoded name. Which physical disk
 * that is — `storage/app/private` or a Cloud bucket — is a deployment decision,
 * and the read path has to follow the write path there or every download 404s
 * on the platform whose disk moved.
 */
trait ServesStoredAttachment
{
    /**
     * How long a signed URL handed to the browser stays valid.
     *
     * A lesson video is watched in one sitting and the URL is minted per request,
     * so this is a window to *start* the stream, not to finish it: the range
     * requests that follow inherit the authorisation of the URL, and the bytes
     * behind it stop being public minutes after the tab moves on.
     */
    private const int STREAM_URL_MINUTES = 10;

    /**
     * @param  string|null  $path  The stored path, relative to the private disk.
     * @param  string  $title  What the record is called, used for the filename.
     */
    private function downloadAttachment(?string $path, string $title): StreamedResponse
    {
        abort_if($path === null || trim($path) === '', 404);
        abort_unless($this->staysInsideTheDisk($path), 404);

        $storage = $this->privateStorage();

        abort_unless($storage->exists($path), 404);

        return $storage->download($path, $this->downloadName($path, $title));
    }

    /**
     * Stream a stored file for in-page playback, with HTTP Range support.
     *
     * `download()` is the wrong tool for a video: it streams the whole body with
     * no `Accept-Ranges`, so a player cannot seek and must buffer from the first
     * byte every time. A {@see BinaryFileResponse} answers a `Range` request
     * with `206 Partial Content`, which is exactly what `<video>` needs — and it
     * needs a filesystem path, so it is only available when the private disk is
     * local.
     *
     * A remote disk has no path, and that is the case a Cloud deployment is in.
     * There the player is handed a short-lived signed URL: the bucket answers
     * `Range` itself, the bytes never travel through PHP, and seeking works.
     * When the disk cannot sign a URL the file is copied to a temporary local
     * one and served with the same `BinaryFileResponse` — slower to start, the
     * same range behaviour, and never a 500 in front of a lesson.
     *
     * The caller has already proven the right to read the record; this method
     * only proves the path names a file inside the disk, the same guard the
     * download path uses.
     */
    private function streamAttachment(?string $path): Response
    {
        abort_if($path === null || trim($path) === '', 404);
        abort_unless($this->staysInsideTheDisk($path), 404);

        $storage = $this->privateStorage();

        abort_unless($storage->exists($path), 404);

        $absolute = $this->localAbsolutePath($storage, $path);

        if ($absolute !== null) {
            // `autoLastModified` (7th argument) is a bool; passing a timestamp here
            // is a TypeError under strict types and turns every playback into a 500.
            return new BinaryFileResponse($absolute, 200, [], true, null, true);
        }

        try {
            return new RedirectResponse($storage->temporaryUrl($path, now()->addMinutes(self::STREAM_URL_MINUTES)));
        } catch (RuntimeException) {
            // The driver cannot sign (plain FTP, a custom disk): fall back to
            // moving the bytes once, locally, rather than failing the lesson.
            return $this->streamFromTemporaryCopy($storage, $path);
        }
    }

    /**
     * The private disk, by the name the deployment gave it.
     */
    private function privateDisk(): string
    {
        return (string) config('filesystems.private', 'local');
    }

    private function privateStorage(): Filesystem
    {
        return Storage::disk($this->privateDisk());
    }

    /**
     * The real path behind a stored file, only when the disk is local.
     *
     * A remote disk has none — Flysystem throws rather than inventing one — and
     * that is an answer, not a failure: it is what routes the stream through a
     * signed URL instead.
     */
    private function localAbsolutePath(Filesystem $storage, string $path): ?string
    {
        $driver = config('filesystems.disks.'.$this->privateDisk().'.driver');

        return $driver === 'local' ? $storage->path($path) : null;
    }

    /**
     * Copy a file off a disk that cannot sign into one that can be served.
     *
     * `deleteFileAfterSend` matters: without it every playback of a remote
     * lesson would leave its full length behind in the temp directory, which is
     * the ephemeral disk space the Cloud runtime is shortest on.
     */
    private function streamFromTemporaryCopy(Filesystem $storage, string $path): BinaryFileResponse
    {
        $temporary = tempnam(sys_get_temp_dir(), 'stream-');

        abort_if($temporary === false, 404);

        $source = $storage->readStream($path);
        $target = fopen($temporary, 'wb');

        if (! is_resource($source) || ! is_resource($target)) {
            abort(404);
        }

        stream_copy_to_stream($source, $target);

        fclose($source);
        fclose($target);

        return (new BinaryFileResponse($temporary, 200, [], true, null, true))->deleteFileAfterSend(true);
    }

    /**
     * Refuse a path that does not name a file inside the disk.
     *
     * The path is written by `store()` and no user writes the column today, which
     * is exactly why this is enforced rather than assumed: "nobody can put this
     * value there" is a fact about today's callers, not about the column, and the
     * failure mode if it ever changes is reading any file the web process can
     * reach — `.env` among them.
     */
    private function staysInsideTheDisk(string $path): bool
    {
        $normalised = str_replace('\\', '/', $path);

        if (str_contains($normalised, "\0")) {
            return false;
        }

        if (str_starts_with($normalised, '/')) {
            return false;
        }

        // Checked segment by segment rather than by substring, so a filename that
        // merely contains dots is not refused.
        foreach (explode('/', $normalised) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * A name the user can recognise.
     *
     * The stored file is a random hash, so serving its own basename would hand
     * someone `9f2c1a7b.pdf` for a document they know as "Term one report".
     */
    private function downloadName(string $path, string $title): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'download';
        }

        return $extension === '' ? $base : $base.'.'.$extension;
    }
}
