import { readFileSync, statSync } from 'node:fs';
import { resolve } from 'node:path';

// A successful SSR compilation alone leaves @vite without its browser manifest.
// Fail the build before Cloud can deploy an application that answers 500.
function requireFile(path) {
    try {
        if (statSync(path).isFile() && statSync(path).size > 0) return;
    } catch {
        // Report the artifact that is missing instead of the filesystem error.
    }
    throw new Error(`Missing build artifact: ${path}`);
}

try {
    const manifestPath = resolve('public/build/manifest.json');
    requireFile(manifestPath);
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));

    for (const entry of ['resources/css/app.css', 'resources/js/app.tsx']) {
        if (typeof manifest?.[entry]?.file !== 'string') {
            throw new Error(`Vite manifest is missing the entry: ${entry}`);
        }
    }

    for (const chunk of Object.values(manifest)) {
        requireFile(resolve('public/build', chunk.file));
        for (const css of chunk.css ?? []) requireFile(resolve('public/build', css));
    }
    requireFile(resolve('bootstrap/ssr/ssr.js'));
    console.log('Browser assets and Inertia SSR bundle verified.');
} catch (error) {
    console.error(`Build verification failed: ${error.message}`);
    process.exitCode = 1;
}
