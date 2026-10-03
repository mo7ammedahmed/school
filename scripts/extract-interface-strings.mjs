/**
 * Collects the English interface strings this app renders.
 *
 * The screens are written in English, and Arabic is filled in from the shared
 * catalog. The catalog only ever learned about a string when a reader happened
 * to open the screen that shows it, which is why Arabic pages kept English
 * words in them: nobody had ever paid for those particular strings yet.
 *
 * This walks the source instead, so `php artisan translations:prefill` can buy
 * the whole dictionary in one sitting — offline, before anyone is waiting on a
 * page. It is deliberately a text scan rather than a full parse: it only needs
 * to find candidate copy, and the command skips anything a translator would not
 * answer as a label.
 *
 *   node scripts/extract-interface-strings.mjs [--out storage/app/interface-strings.json]
 */

import { readdirSync, readFileSync, mkdirSync, writeFileSync, statSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';

const ROOT = process.cwd();
const SOURCE = join(ROOT, 'resources', 'js');

/** Copy that lives somewhere else, or that reads as code rather than words. */
const SKIP_FILES = new Set(['copy.ts', 'ui-copy.ts', 'translation-sweep.ts', 'ui-copy.tsx']);

/** Strings that are markup, data or identifiers wearing words as a disguise. */
const NOT_COPY = new Set([
    'div',
    'span',
    'button',
    'input',
    'svg',
    'path',
    'flex',
    'grid',
    'true',
    'false',
    'null',
    'undefined',
    'GET',
    'POST',
    'PUT',
    'DELETE',
    'PATCH',
    'en',
    'ar',
    'rtl',
    'ltr',
    'lucide',
    'asc',
    'desc',
]);

const MAX_LENGTH = 300;
const MIN_LENGTH = 2;

/** A string with any of these is code, an interpolation, or a URL. */
const CODEY = /[;{}()[\]<>]|\$\{|=>|&&|\|\||\/\/|@|https?:|`|\\/;

const onlyWords = /^[\p{Sc}\p{P}\p{Zs}]*[\p{L}][\p{L}\p{N}\p{P}\p{Zs}\p{Sc}]*$/u;

function isCopy(value) {
    const text = value.replace(/\s+/g, ' ').trim();

    if (text.length < MIN_LENGTH || text.length > MAX_LENGTH) return false;
    if (!/[A-Za-z]{2}/.test(text)) return false;
    if (CODEY.test(text)) return false;
    if (NOT_COPY.has(text)) return false;
    if (!onlyWords.test(text)) return false;

    // Copy starts with a word: leading punctuation means a list, a ticket
    // reference or a fragment of code, not a label.
    if (!/^\p{L}/u.test(text)) return false;
    // A single lowercase word is a key, a slug or a CSS utility, not a label.
    if (/^[a-z][a-z0-9_-]*$/.test(text)) return false;
    // `row.original.is_active ?` — a condition that happens to sit between two
    // tags, not a label.
    if (/^[A-Za-z_$][\w$.]*\s*\?$/.test(text)) return false;
    // A path or a file name.
    if (/^[\w-]+(\.[\w-]+)+$/.test(text)) return false;
    // "OK" and two-letter fragments are truncations or codes, not labels — but
    // a three-letter word is a real one ("View", "Edit", "Yes"), and the rule
    // used to be a five-letter one, which kept every row action in English on
    // an Arabic page.
    if (!text.includes(' ') && text.length < 3) return false;

    for (const character of text) {
        if ((character.codePointAt(0) ?? 0) > 0x7f && /[\p{L}\p{N}]/u.test(character)) return false;
    }

    return true;
}

function walk(dir, files = []) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const full = join(dir, entry.name);

        if (entry.isDirectory()) walk(full, files);
        // Test fixtures read like screens but are never rendered, and a string
        // that only exists in a test is one nobody will ever read.
        else if (/\.tsx?$/.test(entry.name) && !entry.name.endsWith('.d.ts') && !/\.test\.tsx?$/.test(entry.name)) {
            files.push(full);
        }
    }

    return files;
}

/** Comment and import lines carry prose-like words that are never rendered. */
function stripNoise(source) {
    return source
        .replace(/\/\*[\s\S]*?\*\//g, ' ')
        .replace(/^\s*\/\/.*$/gm, ' ')
        .replace(/^\s*import\s.*$/gm, ' ');
}

function collect(source, found) {
    const add = (value) => {
        if (isCopy(value)) found.add(value.replace(/\s+/g, ' ').trim());
    };

    // Text sitting between tags: <h1>Students</h1>
    for (const match of source.matchAll(/>([^<>{}]{2,300}?)</g)) add(match[1]);

    // Copy handed to a prop or a config object: label="Students", title: 'Fees'
    const props = /(?:label|title|placeholder|aria-label|ariaLabel|alt|description|heading|subtitle|caption|emptyMessage|errorMessage|helper|hint|tooltip|confirmText)\s*[:=]\s*(?:"([^"\n]{2,300})"|'([^'\n]{2,300})'|`([^`\n]{2,300})`)/g;

    for (const match of source.matchAll(props)) add(match[1] ?? match[2] ?? match[3] ?? '');
}

function main() {
    const outIndex = process.argv.indexOf('--out');
    const out = outIndex !== -1 ? process.argv[outIndex + 1] : join(ROOT, 'storage', 'app', 'interface-strings.json');

    const files = walk(SOURCE);
    const found = new Set();
    let scanned = 0;

    for (const file of files) {
        if (SKIP_FILES.has(file.split(/[\\/]/).pop())) continue;

        collect(stripNoise(readFileSync(file, 'utf8')), found);
        scanned++;
    }

    const strings = [...found].sort();

    mkdirSync(dirname(out), { recursive: true });
    writeFileSync(out, `${JSON.stringify({ generatedFrom: relative(ROOT, SOURCE), files: scanned, strings }, null, 2)}\n`);

    console.log(`scanned ${scanned} files`);
    console.log(`${strings.length} candidate interface strings -> ${relative(ROOT, out)}`);
    console.log('');
    console.log('a few of them:');

    for (const sample of strings.slice(0, 12)) console.log(`  ${sample}`);
}

main();
