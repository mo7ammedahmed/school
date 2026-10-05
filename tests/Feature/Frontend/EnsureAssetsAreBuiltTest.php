<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * `assets:ensure` runs from `post-install-cmd`, so the path every developer
 * meets is the fast one: a manifest that already exists must mean no shell and
 * no Node. The build path is exercised against a faked process — a test that
 * really ran npm would take a minute and prove the toolchain, not the command.
 *
 * The real manifest is moved aside for the duration and restored in tearDown,
 * so a failed assertion cannot leave a checkout that throws
 * `ViteManifestNotFoundException` on every page.
 */
class EnsureAssetsAreBuiltTest extends TestCase
{
    /** Where the real manifest was moved to, when one was moved. */
    private ?string $parkedManifest = null;

    /** Whether this test created a placeholder manifest that it must remove. */
    private bool $createdManifest = false;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = public_path('build/manifest.json');
    }

    protected function tearDown(): void
    {
        if ($this->parkedManifest !== null) {
            rename($this->parkedManifest, $this->manifestPath);
            $this->parkedManifest = null;
        }

        if ($this->createdManifest) {
            @unlink($this->manifestPath);
            $this->createdManifest = false;
        }

        parent::tearDown();
    }

    public function test_an_existing_manifest_means_the_build_is_skipped(): void
    {
        $this->ensureManifestExists();

        Process::fake();

        $this->artisan('assets:ensure')
            ->expectsOutputToContain('Vite manifest is present')
            ->assertSuccessful();

        Process::assertNothingRan();
    }

    public function test_a_missing_manifest_without_npm_warns_instead_of_failing(): void
    {
        $this->parkManifest();

        Process::fake([
            'npm --version' => Process::result(errorOutput: 'not found', exitCode: 127),
        ]);

        $this->artisan('assets:ensure')
            ->expectsOutputToContain('npm is not available')
            ->assertSuccessful();

        Process::assertNotRan(fn (PendingProcess $process): bool => str_contains(
            is_array($process->command) ? implode(' ', $process->command) : (string) $process->command,
            'npm run build',
        ));
    }

    public function test_a_build_that_never_produces_a_manifest_fails(): void
    {
        $this->parkManifest();

        Process::fake([
            'npm --version' => Process::result('10.0.0'),
            'npm run build' => Process::result('built'),
        ]);

        $this->artisan('assets:ensure')
            ->expectsOutputToContain('without producing')
            ->assertFailed();
    }

    /**
     * Use the real manifest when this checkout has one, and a placeholder when
     * it does not (a fresh clone, or CI, where nothing has run `npm run build`).
     */
    private function ensureManifestExists(): void
    {
        if (is_file($this->manifestPath)) {
            return;
        }

        @mkdir(dirname($this->manifestPath), 0755, true);

        file_put_contents($this->manifestPath, '{}');

        $this->createdManifest = true;
    }

    /**
     * Hide the manifest from the command, remembering where it went.
     */
    private function parkManifest(): void
    {
        if (! is_file($this->manifestPath)) {
            return;
        }

        $this->parkedManifest = $this->manifestPath.'.parked';

        rename($this->manifestPath, $this->parkedManifest);
    }
}
