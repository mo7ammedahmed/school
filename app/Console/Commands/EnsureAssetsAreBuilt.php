<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Build the frontend bundles when the Vite manifest is missing.
 *
 * `public/build` is git-ignored, so the manifest only exists on a machine that
 * has run `npm run build`. A host whose build step stops at `composer install`
 * therefore deploys code that throws `ViteManifestNotFoundException` on every
 * page — a 500 with no hint that the build step, not the code, is the problem.
 *
 * This runs from `post-install-cmd`, which every `composer install` fires, so
 * the assets are built even when the platform's build command forgets them.
 * It is a no-op wherever the manifest is already there, so local development
 * pays nothing for it, and it refuses to pretend: a build that finishes without
 * producing a manifest is a failure, not a success.
 *
 * `npm ci` is used when `node_modules` is absent (a fresh build container) and
 * plain `npm run build` when it is present, because `npm ci` deletes the tree
 * first and is only worth its cost when there is nothing to reuse.
 */
class EnsureAssetsAreBuilt extends Command
{
    protected $signature = 'assets:ensure
                            {--force : Build even when the manifest already exists}';

    protected $description = 'Build the Vite bundles when public/build/manifest.json is missing';

    private const string MANIFEST = 'public/build/manifest.json';

    public function handle(): int
    {
        if (! $this->option('force') && is_file(base_path(self::MANIFEST))) {
            $this->components->info('Vite manifest is present; nothing to build.');

            return self::SUCCESS;
        }

        if (! $this->npmIsAvailable()) {
            // Not an error: a PHP-only environment (a linter, a phpunit run)
            // has no reason to carry Node, and failing `composer install`
            // there would be a worse outcome than the missing assets.
            $this->components->warn('npm is not available; skipping the frontend build.');

            return self::SUCCESS;
        }

        $command = is_dir(base_path('node_modules'))
            ? 'npm run build'
            : 'npm ci && npm run build';

        $this->components->info("Vite manifest missing — running `{$command}`.");

        $result = Process::path(base_path())
            ->timeout(900)
            ->run($command);

        if ($result->failed()) {
            $this->components->error('The frontend build failed.');

            $this->line($result->errorOutput() !== '' ? $result->errorOutput() : $result->output());

            return self::FAILURE;
        }

        if (! is_file(base_path(self::MANIFEST))) {
            $this->components->error('The build finished without producing '.self::MANIFEST.'.');

            return self::FAILURE;
        }

        $this->components->info('Frontend assets built.');

        return self::SUCCESS;
    }

    /**
     * Whether a shell can find npm at all.
     *
     * The command is a shell string rather than an argument list so the same
     * lookup works on Windows, where npm is a `.cmd` shim that only the shell
     * resolves through `PATHEXT`.
     */
    private function npmIsAvailable(): bool
    {
        return Process::timeout(60)->run('npm --version')->successful();
    }
}
