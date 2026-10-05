// @vitest-environment node
/// <reference types="node" />
import { afterEach, describe, expect, it } from 'vitest';
import { spawnSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const checker = fileURLToPath(new URL('../../scripts/check-build.mjs', import.meta.url));
const directories: string[] = [];

afterEach(() => {
    for (const directory of directories.splice(0)) {
        if (dirname(resolve(directory)) !== resolve(tmpdir()) || !basename(directory).startsWith('school-build-check-')) {
            throw new Error('Refusing to remove a directory outside the build test fixtures.');
        }
        rmSync(directory, { recursive: true, force: true });
    }
});

function fixture() {
    const root = mkdtempSync(join(tmpdir(), 'school-build-check-'));
    directories.push(root);
    const write = (path: string, content = 'built') => {
        mkdirSync(dirname(join(root, path)), { recursive: true });
        writeFileSync(join(root, path), content);
    };
    const check = () => spawnSync(process.execPath, [checker], { cwd: root, encoding: 'utf8' });
    return { write, check };
}

const manifest = {
    'resources/css/app.css': { file: 'assets/app.css' },
    'resources/js/app.tsx': { file: 'assets/app.js', css: ['assets/app.css'] },
    '_shared.js': { file: 'assets/shared.js' },
};

describe('deployment build artifacts', () => {
    it('rejects an SSR-only build before deployment', () => {
        const { write, check } = fixture();
        write('bootstrap/ssr/ssr.js');
        const result = check();
        expect(result.status).toBe(1);
        expect(result.stderr).toContain('manifest.json');
    });

    it('rejects a manifest whose referenced browser chunk is missing', () => {
        const { write, check } = fixture();
        write('public/build/manifest.json', JSON.stringify(manifest));
        write('public/build/assets/app.css');
        write('public/build/assets/app.js');
        write('bootstrap/ssr/ssr.js');
        const result = check();
        expect(result.status).toBe(1);
        expect(result.stderr).toContain('shared.js');
    });

    it('rejects a browser-only build and accepts both complete bundles', () => {
        const { write, check } = fixture();
        write('public/build/manifest.json', JSON.stringify(manifest));
        for (const chunk of Object.values(manifest)) write(`public/build/${chunk.file}`);
        expect(check().status).toBe(1);
        write('bootstrap/ssr/ssr.js');
        expect(check().status).toBe(0);
    });
});
