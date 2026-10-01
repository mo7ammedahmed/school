<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `inertia('content/pages/edit')` is a string, and nothing between the
 * controller and the browser checks that the file it names exists. The client
 * resolves `./Pages/{name}.tsx` inside `createInertiaApp`, so a wrong name is a
 * page that answers the redirect and then renders nothing: the content-page
 * editor was reachable only as a 500-ish dead end after every create and update,
 * while `apply/index.tsx` and `messages/edit.tsx` sat in the tree as screens
 * nobody could open.
 *
 * Both directions are properties of the tree plus the render calls, so they are
 * checked here rather than screen by screen. This case was written red against
 * one missing component (`content/pages/edit`) and the two orphaned files.
 */
class EveryRenderedPageHasAComponentTest extends TestCase
{
    public function test_every_page_name_a_controller_renders_has_a_component(): void
    {
        $missing = [];

        foreach ($this->renderedPageNames() as $name => $callers) {
            if (! is_file($this->componentPath($name))) {
                $missing[] = sprintf('  %s -> %s', $name, implode(', ', $callers));
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These pages are rendered by PHP but no component file answers them:\n".implode("\n", $missing),
        );
    }

    public function test_every_page_component_is_rendered_somewhere(): void
    {
        $rendered = array_keys($this->renderedPageNames());
        $orphans = [];

        foreach (array_keys($this->components()) as $name) {
            if (! in_array($name, $rendered, true)) {
                $orphans[] = '  '.$name.'.tsx';
            }
        }

        $this->assertSame(
            [],
            $orphans,
            "These component files under resources/js/Pages are never rendered:\n".implode("\n", $orphans),
        );
    }

    /**
     * Every literal page name a controller or route renders, keyed by name and
     * carrying the files that ask for it, so a failure names the call site.
     *
     * @return array<string, list<string>>
     */
    private function renderedPageNames(): array
    {
        $names = [];

        foreach ($this->phpSources() as $path) {
            preg_match_all(
                "/(?:inertia|Inertia::render)\(\s*'([^']+)'/",
                (string) file_get_contents($path),
                $matches,
            );

            foreach (array_unique($matches[1]) as $name) {
                $names[$name][] = $this->relative($path);
            }
        }

        ksort($names);

        return $names;
    }

    /**
     * Component files that Inertia can resolve, keyed by page name.
     *
     * The glob in `resources/js/app.tsx` excludes `*.test.tsx` for the same
     * reason: a test file next to a page is not a page.
     *
     * @return array<string, string>
     */
    private function components(): array
    {
        $pages = [];

        foreach (File::allFiles(resource_path('js/Pages')) as $file) {
            if ($file->getExtension() !== 'tsx' || str_ends_with($file->getFilename(), '.test.tsx')) {
                continue;
            }

            // `apply/index.tsx` is the page name `apply/index`.
            $pages[str_replace('\\', '/', substr($file->getRelativePathname(), 0, -4))] = $file->getPathname();
        }

        return $pages;
    }

    private function componentPath(string $name): string
    {
        return resource_path("js/Pages/{$name}.tsx");
    }

    /**
     * @return list<string>
     */
    private function phpSources(): array
    {
        $sources = [];

        foreach ([app_path(), base_path('routes')] as $root) {
            foreach (File::allFiles($root) as $file) {
                if ($file->getExtension() === 'php') {
                    $sources[] = $file->getPathname();
                }
            }
        }

        sort($sources);

        return $sources;
    }

    private function relative(string $path): string
    {
        return str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
    }
}
