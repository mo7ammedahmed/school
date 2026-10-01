<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Documents\Models\Document;
use App\Domain\Learning\Models\Material;
use App\Domain\Learning\Models\Submission;
use Illuminate\Console\Command;
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

    protected $description = "Move uploaded files off the public disk, where the web server will serve them";

    /**
     * The models whose uploads used to land on the public disk.
     *
     * @var list<class-string<\Illuminate\Database\Eloquent\Model>>
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

        foreach (self::MODELS as $model) {
            $model::withoutSchoolScope()
                ->withTrashed()
                ->whereNotNull('file_path')
                ->where('file_path', '!=', '')
                ->chunkById(200, function ($rows) use (&$moved, &$removedDuplicates, &$missing, $dryRun): void {
                    foreach ($rows as $row) {
                        $path = (string) $row->file_path;

                        if (! Storage::disk('public')->exists($path)) {
                            $missing++;

                            continue;
                        }

                        if (Storage::disk('local')->exists($path)) {
                            // The upload was replaced after the disk change, so
                            // the private copy is the newer one. Removing the
                            // public copy is the point; overwriting it is not.
                            $this->removePublicCopy($path, $dryRun);
                            $removedDuplicates++;

                            continue;
                        }

                        if (! $dryRun) {
                            $this->moveToPrivateDisk($path);
                        }

                        $moved++;
                    }
                });
        }

        $this->report($moved, $removedDuplicates, $missing, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Stream the file across rather than reading it into memory.
     *
     * A 10 MB upload is within the size limit the app accepts, and a command that
     * loads one into memory per row is a command that can be made to run out of
     * it by a directory full of large files.
     */
    private function moveToPrivateDisk(string $path): void
    {
        $stream = Storage::disk('public')->readStream($path);

        if ($stream === false) {
            return;
        }

        Storage::disk('local')->writeStream($path, $stream);

        Storage::disk('public')->delete($path);
    }

    private function removePublicCopy(string $path, bool $dryRun): void
    {
        if (! $dryRun) {
            Storage::disk('public')->delete($path);
        }
    }

    private function report(int $moved, int $removedDuplicates, int $missing, bool $dryRun): void
    {
        $prefix = $dryRun ? 'Would relocate' : 'Relocated';

        $this->line("{$prefix} {$moved} file(s) to private storage.");
        $this->line("Removed {$removedDuplicates} stale public copy/copies already present in private storage.");

        if ($missing > 0) {
            // Said out loud because a row pointing at nothing is a record of a
            // file nobody can produce any more, and a silent command would let
            // that be mistaken for "nothing to do".
            $this->warn("{$missing} row(s) point at a file that is on neither disk; they were left alone.");
        }
    }
}