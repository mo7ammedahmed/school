<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Documents\Models\Document;
use App\Domain\Learning\Models\Material;
use App\Domain\Learning\Models\Submission;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Moves files that were uploaded to the `public` disk onto the private one.
 *
 * Uploads stopped being written to `public` because anything there is exposed
 * inside the document root by `storage:link`, and a file served from there with
 * a `.php` extension is executed by the web server. That closes the hole for new
 * uploads and leaves it open for everything already uploaded, which is the
 * majority of a school's documents on the day the change ships.
 *
 * **The stored path does not change.** A row says `documents/abc.pdf` and the
 * controller chooses the disk, so putting the file at the same relative path on
 * the private disk leaves every row valid, every screen working, and every link
 * already emailed to somebody still resolving to the same record. There is no
 * data migration and nothing to roll back but the file move itself.
 *
 * Rows are read with `withoutSchoolScope()` and `withTrashed()`. A console command
 * has no tenant context and the tenant scope's deliberate answer to that is "no
 * rows" — a sweep that saw zero documents would report success and leave every
 * school's files exposed. Trashed rows are included because a soft-deleted
 * document's file is still in the document root and still executable.
 */
class RelocatePublicUploads extends Command
{
    protected $signature = 'uploads:relocate
                            {--dry-run : Report what would move without touching anything}';

    protected $description = 'Move uploaded files off the public disk, where the web server will serve them';

    /**
     * The models whose uploads used to land on the public disk.
     *
     * @var list<class-string<Model>>
     */
    private const MODELS = [
        Document::class,
        Material::class,
        Submission::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $moved = 0;
        $removedDuplicates = 0;
        $missing = 0;
        $unreadable = 0;

        foreach (self::MODELS as $model) {
            $model::withoutSchoolScope()
                ->withTrashed()
                ->whereNotNull('file_path')
                ->where('file_path', '!=', '')
                ->chunkById(200, function ($rows) use (&$moved, &$removedDuplicates, &$missing, &$unreadable, $dryRun): void {
                    foreach ($rows as $row) {
                        $path = (string) $row->file_path;

                        if (! Storage::disk('public')->exists($path)) {
                            $missing++;

                            continue;
                        }

                        if (Storage::disk($this->privateDisk())->exists($path)) {
                            // The upload was replaced after the disk change, so
                            // the private copy is the newer one. Removing the
                            // public copy is the point; overwriting it is not.
                            $this->removePublicCopy($path, $dryRun);
                            $removedDuplicates++;

                            continue;
                        }

                        if (! $dryRun && ! $this->moveToPrivateDisk($path)) {
                            $unreadable++;

                            continue;
                        }

                        $moved++;
                    }
                });
        }

        $this->report($moved, $removedDuplicates, $missing, $unreadable, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Stream the file across rather than reading it into memory.
     *
     * A 10 MB upload is within the size limit the app accepts, and a command that
     * loads one into memory per row is a command that can be made to run out of
     * it by a directory full of large files.
     *
     * Returns false when the file could not be read. The public copy is left in
     * place in that case, so reporting the row as relocated would be a lie — the
     * file is still exactly where it was, still inside the document root.
     */
    private function moveToPrivateDisk(string $path): bool
    {
        $stream = Storage::disk('public')->readStream($path);

        if ($stream === null) {
            return false;
        }

        Storage::disk($this->privateDisk())->writeStream($path, $stream);

        Storage::disk('public')->delete($path);

        return true;
    }

    /**
     * The disk the relocation is *to*, by the name the deployment gave it.
     *
     * `local` on a machine whose storage persists, a bucket on Laravel Cloud.
     * The point of the command is to get uploads out of the document root, and
     * which private disk they land on is the same decision every upload makes.
     */
    private function privateDisk(): string
    {
        return (string) config('filesystems.private', 'local');
    }

    private function removePublicCopy(string $path, bool $dryRun): void
    {
        if (! $dryRun) {
            Storage::disk('public')->delete($path);
        }
    }

    private function report(int $moved, int $removedDuplicates, int $missing, int $unreadable, bool $dryRun): void
    {
        $prefix = $dryRun ? 'Would relocate' : 'Relocated';

        $this->line("{$prefix} {$moved} file(s) to private storage.");
        $this->line("Removed {$removedDuplicates} stale public copy/copies already present in private storage.");

        if ($unreadable > 0) {
            $this->error(
                "{$unreadable} file(s) could not be read and are STILL in the document root. "
                .'Check their permissions and run the command again.',
            );
        }

        if ($missing > 0) {
            // Said out loud because a row pointing at nothing is a record of a
            // file nobody can produce any more, and a silent command would let
            // that be mistaken for "nothing to do".
            $this->warn("{$missing} row(s) point at a file that is on neither disk; they were left alone.");
        }
    }
}
