<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
 */
trait ServesStoredAttachment
{
    private const string DOWNLOAD_DISK = 'local';

    /**
     * @param  string|null  $path  The stored path, relative to the private disk.
     * @param  string  $title  What the record is called, used for the filename.
     */
    private function downloadAttachment(?string $path, string $title): StreamedResponse
    {
        abort_if($path === null || trim($path) === '', 404);
        abort_unless($this->staysInsideTheDisk($path), 404);

        $storage = Storage::disk(self::DOWNLOAD_DISK);

        abort_unless($storage->exists($path), 404);

        return $storage->download($path, $this->downloadName($path, $title));
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
