<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Tests\TestCase;

/**
 * Server-side rendering is four files agreeing with each other, and every way
 * of getting it wrong is silent: Inertia checks that an SSR bundle exists, and
 * if it does not, it renders in the browser without a word. So an entry point
 * nobody built, a `build` script that only builds the client, or an SSR tree
 * that drifted from the client's all look like a working application — one that
 * simply never server-renders.
 *
 * The runtime half of this guard is `resources/js/ssr-render.test.tsx`, which
 * renders a page under Node with no DOM; this half pins the wiring that has to
 * exist before that render can ever run in production.
 */
class SsrIsWiredTest extends TestCase
{
    public function test_the_ssr_entry_point_exists_and_is_what_vite_builds(): void
    {
        $entry = 'resources/js/ssr.tsx';

        $this->assertFileExists(
            base_path($entry),
            'Inertia looks for bootstrap/ssr/ssr.js, which only exists if an SSR entry is built.',
        );

        $vite = (string) file_get_contents(base_path('vite.config.js'));

        $this->assertMatchesRegularExpression(
            "/ssr:\s*'".preg_quote($entry, '/')."'/",
            $vite,
            "vite.config.js must name {$entry} as the laravel() plugin's `ssr` input.",
        );
    }

    public function test_the_build_script_builds_both_bundles(): void
    {
        $scripts = json_decode((string) file_get_contents(base_path('package.json')), true)['scripts'];

        $this->assertSame(
            'vite build && vite build --ssr',
            $scripts['build'] ?? null,
            'A deployment that runs only `vite build` ships no SSR bundle, and SSR then switches itself off.',
        );

        $this->assertSame('vite build --ssr', $scripts['build:ssr'] ?? null);
    }

    public function test_the_ssr_bundle_is_a_build_artifact(): void
    {
        $this->assertStringContainsString(
            '/bootstrap/ssr',
            (string) file_get_contents(base_path('.gitignore')),
            'The compiled SSR bundle is produced by the build, not committed.',
        );
    }

    /**
     * The server's markup and the browser's first render have to be the same
     * tree. React hydrates only what matches, and what does not match is thrown
     * away — so both entries render through the one root in `resources/js/root`.
     */
    public function test_both_entries_render_through_the_shared_root(): void
    {
        $client = (string) file_get_contents(resource_path('js/app.tsx'));
        $server = (string) file_get_contents(resource_path('js/ssr-render.tsx'));

        foreach (['app.tsx' => $client, 'ssr-render.tsx' => $server] as $file => $source) {
            $this->assertStringContainsString("from '@/root'", $source, "{$file} must import the shared root.");
            $this->assertStringContainsString('InertiaRoot', $source, "{$file} must render through InertiaRoot.");
            $this->assertStringContainsString('resolve:', $source, "{$file} must resolve pages the shared way.");
        }

        $this->assertStringContainsString(
            'hydrateRoot(el, tree)',
            $client,
            'The client must hydrate server-rendered markup instead of mounting over it.',
        );

        $this->assertStringNotContainsString(
            'document.',
            $server,
            'ssr-render.tsx runs where there is no document: browser work belongs in app.tsx.',
        );
    }
}
