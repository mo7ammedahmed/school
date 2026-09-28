import type { Locale } from './copy';

/**
 * Translates the dashboard's own interface words when Arabic is selected.
 *
 * The dashboard was written in English — hundreds of screens of headings,
 * labels and buttons — so choosing Arabic used to leave English chrome wrapped
 * around Arabic content. Instead of leaving the choice half-working until every
 * screen is re-typed by hand, this walks the visible *interface* text, asks the
 * server (which uses the school's own translation provider, cached per string),
 * and writes the Arabic back into the page. Content is deliberately out of
 * reach: table cells and anything marked `data-no-translate` are left alone, so
 * a student's name is never machine-translated.
 */

const STORAGE_KEY = 'aether.ui-copy.v1';

/** Interface text, not data: chrome, headings, controls and table headers. */
const SELECTOR = [
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'p',
    'span',
    'label',
    'button',
    'a',
    'th',
    'legend',
    'summary',
    'option',
    'dt',
    'li',
].join(',');

const ATTRIBUTES = ['placeholder', 'title', 'aria-label'] as const;

const SKIP_TAGS = ['SCRIPT', 'STYLE', 'CODE', 'PRE', 'KBD', 'SAMP', 'SVG', 'TEXTAREA'];

/** Beyond this a string is prose — an article body, not a label. */
const MAX_LENGTH = 300;

const memory = new Map<string, string>();
const requested = new Set<string>();

/**
 * The provider takes seconds per string, so a screen full of copy would take
 * minutes one word at a time. Asking for a few short slices at once is what
 * keeps the first visit to a page bearable; after that the strings come from
 * the cache and nothing is asked at all.
 */
const CONCURRENCY = 4;
const SLICE = 6;
const ROUNDS = 6;

/** A string the provider refuses to answer is not worth asking about again. */
const attempts = new Map<string, number>();
const MAX_ATTEMPTS = 5;

/**
 * Every write is remembered so the switch back to English can undo it.
 *
 * React does not re-check the text it already committed, so an element whose
 * English string is unchanged between renders keeps whatever we wrote into it —
 * hence the undo has to live here rather than in the component tree.
 */
type TextWrite = { node: Text; original: string; applied: string };
type AttributeWrite = { element: Element; attribute: string; original: string; applied: string };

const writtenText: TextWrite[] = [];
const writtenAttributes: AttributeWrite[] = [];

let observer: MutationObserver | null = null;
let debounce: number | null = null;

/** Newlines and the like never belong in a label; tabs and spaces do. */
// eslint-disable-next-line no-control-regex
const CONTROL = /[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/;

/**
 * Only English interface copy qualifies.
 *
 * A letter from another script — Arabic content, a student's name — means this
 * is not the translator's business, which is what keeps the pass to the
 * interface. Typography is a different matter: the app's copy is full of em
 * dashes, curly quotes and ellipses, and a sentence holding one of those is
 * still a sentence.
 */
function isTranslatable(value: string): boolean {
    if (value.length < 2 || value.length > MAX_LENGTH) return false;
    // eslint-disable-next-line no-control-regex
    if (CONTROL.test(value)) return false;
    if (!/[A-Za-z]{2}/.test(value)) return false;
    if (/@|https?:|\/\//.test(value)) return false;

    for (const character of value) {
        if ((character.codePointAt(0) ?? 0) > 0x7f && /[\p{L}\p{N}]/u.test(character)) {
            return false;
        }
    }

    return true;
}

function load(): void {
    if (memory.size > 0 || typeof window === 'undefined') return;

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) return;

        for (const [english, arabic] of Object.entries(JSON.parse(raw) as Record<string, string>)) {
            memory.set(english, arabic);
        }
    } catch {
        // A corrupt cache is not worth failing a page over.
    }
}

function save(): void {
    try {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(Object.fromEntries(memory)),
        );
    } catch {
        // Storage full or blocked: the translations still work for this visit.
    }
}

function skipped(element: Element): boolean {
    if (SKIP_TAGS.includes(element.tagName)) return true;
    // A table cell is data, and data belongs to the school, not the translator.
    if (element.closest('td, [data-no-translate], [contenteditable="true"]')) return true;

    return false;
}

/** Swaps in whatever is already known, leaving unknown strings in English. */
function apply(root: ParentNode): string[] {
    const missing = new Set<string>();

    root.querySelectorAll(SELECTOR).forEach((element) => {
        if (skipped(element)) return;

        element.childNodes.forEach((node) => {
            if (!(node instanceof Text)) return;

            const raw = node.nodeValue ?? '';
            const value = raw.trim();

            if (!isTranslatable(value)) return;

            const arabic = memory.get(value);

            // The provider may hand a proper noun straight back. Writing the
            // same text would fire a mutation record and set this pass going
            // again for ever, so only a real change is written.
            if (arabic && arabic !== value) {
                // Whitespace is preserved so inline text keeps its spacing.
                const applied = raw.replace(value, arabic);

                writtenText.push({ node, original: raw, applied });
                node.nodeValue = applied;
            } else if (!arabic) {
                missing.add(value);
            }
        });

        ATTRIBUTES.forEach((attribute) => {
            const value = element.getAttribute(attribute)?.trim();

            if (!value || !isTranslatable(value)) return;

            const arabic = memory.get(value);

            if (arabic && arabic !== value) {
                writtenAttributes.push({ element, attribute, original: value, applied: arabic });
                element.setAttribute(attribute, arabic);
            } else if (!arabic) {
                missing.add(value);
            }
        });
    });

    return [...missing];
}

/**
 * Puts the English words back.
 *
 * A node is only restored when it still holds what we wrote: if React has since
 * re-rendered it with newer copy, that copy is the truth and is left alone.
 */
function restore(): void {
    writtenText.forEach(({ node, original, applied }) => {
        if (node.isConnected && node.nodeValue === applied) {
            node.nodeValue = original;
        }
    });

    writtenAttributes.forEach(({ element, attribute, original, applied }) => {
        if (element.isConnected && element.getAttribute(attribute) === applied) {
            element.setAttribute(attribute, original);
        }
    });

    writtenText.length = 0;
    writtenAttributes.length = 0;
}async function fetchMissing(strings: string[]): Promise<boolean> {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    // One request per string: the same word can appear all over a screen.
    const wanted = [...new Set(strings)]
        .filter((value) => !requested.has(value) && (attempts.get(value) ?? 0) < MAX_ATTEMPTS)
        .slice(0, 200);

    if (wanted.length === 0) return true;

    wanted.forEach((value) => requested.add(value));

    try {
        const response = await fetch('/ui/copy', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ strings: wanted }),
        });

        const payload = (await response.json().catch(() => ({}))) as {
            translations?: Record<string, string>;
            pending?: string[];
        };

        for (const [english, arabic] of Object.entries(payload.translations ?? {})) {
            memory.set(english, arabic);
        }

        save();

        // Asked and not answered: allow another attempt on the next pass rather
        // than leaving the string marked as in flight for ever. A string that
        // ran out of the server's clock is not a failure, just unfinished.
        const stalled = new Set(payload.pending ?? []);

        stalled.forEach((value) => requested.delete(value));

        wanted.forEach((value) => {
            requested.delete(value);

            if (!memory.has(value) && !stalled.has(value)) {
                // Nothing came back at all. Once the provider has refused a
                // string a few times, leave it in readable English.
                attempts.set(value, (attempts.get(value) ?? 0) + 1);
            }
        });

        // Too many requests in a row: stop the round rather than push harder.
        return response.status !== 429;
    } catch {
        wanted.forEach((value) => requested.delete(value));

        return false;
    }
}

let running = false;
let queued = false;

/** Writes are debounced: a screenful of React updates is one pass, not fifty. */
function schedule(): void {
    if (typeof window === 'undefined') return;

    if (debounce !== null) window.clearTimeout(debounce);

    debounce = window.setTimeout(() => {
        debounce = null;
        void pass();
    }, 150);
}

async function pass(root: ParentNode = document.body): Promise<void> {
    // A page that keeps changing while a pass is running gets another one
    // afterwards rather than having its words dropped on the floor.
    if (running) {
        queued = true;

        return;
    }

    running = true;

    try {
        let missing = apply(root);

        // A batch that could not finish (the server has its own clock) is asked
        // for again straight away, so one page view fills the screen.
        for (let round = 0; round < ROUNDS && missing.length > 0; round++) {
            const slices: string[][] = [];

            for (let start = 0; start < missing.length && slices.length < CONCURRENCY; start += SLICE) {
                slices.push(missing.slice(start, start + SLICE));
            }

            const answers = await Promise.all(slices.map((slice) => fetchMissing(slice)));

            missing = apply(root);

            // Nothing more to gain from another round when the answer was
            // "too many requests" or the network is gone.
            if (answers.some((answered) => !answered)) break;
        }
    } finally {
        running = false;

        if (queued) {
            queued = false;
            schedule();
        }
    }
}

/**
 * Starts translating the page copy, and stops when the locale is not Arabic.
 * Returns the teardown for the effect that owns it.
 */
export function translateInterfaceCopy(locale: Locale): () => void {
    if (typeof window === 'undefined' || locale !== 'ar') {
        return () => undefined;
    }

    load();

    schedule();

    observer = new MutationObserver(schedule);
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });

    return () => {
        observer?.disconnect();
        observer = null;
        queued = false;

        if (debounce !== null) {
            window.clearTimeout(debounce);
            debounce = null;
        }

        restore();
    };
}
