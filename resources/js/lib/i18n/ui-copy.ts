import { handWrittenArabic, type Locale } from './copy';
import { router } from '@inertiajs/react';

/**
 * Translates the dashboard's own interface words when Arabic is selected.
 *
 * The dashboard was written in English — hundreds of screens of headings,
 * labels and buttons — so choosing Arabic used to leave English chrome wrapped
 * around Arabic content. This walks visible interface copy and applies the
 * school's shared catalog to it.
 *
 * The catalog is the point: the browser is handed the whole dictionary with the
 * page (see `InterfaceCatalog` on the server), so the words that are already
 * known are painted before the first frame. There is no provider call in the
 * path of a page load at all. Whatever is genuinely new is asked for once,
 * afterwards, while the reader is already looking at the page — six strings at a
 * time in the middle of a render is what used to make an Arabic screen take
 * tens of seconds to arrive, and it is what this avoids.
 *
 * Content is deliberately out of reach: table cells and anything marked
 * `data-no-translate` are left alone.
 */

/** The dictionary, as the server sends it. */
export interface InterfaceCatalog {
    version: string;
    translations: Record<string, string>;
}

const CATALOG_ENDPOINT = '/ui/copy/catalog';
const VERSION_ENDPOINT = '/ui/copy/version';
const FILL_ENDPOINT = '/ui/copy';

const STORAGE_KEY = 'aether.ui-copy.v2';

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
    'input',
    '[placeholder]',
    '[title]',
    '[aria-label]',
].join(',');

const ATTRIBUTES = ['placeholder', 'title', 'aria-label'] as const;

const SKIP_TAGS = ['SCRIPT', 'STYLE', 'CODE', 'PRE', 'KBD', 'SAMP', 'SVG', 'TEXTAREA'];

/** Beyond this a string is prose — an article body, not a label. */
const MAX_LENGTH = 300;

/**
 * How many unseen strings one page view may ask the provider about.
 *
 * One request, once, after the page is on screen. A screen that needs more than
 * this simply finishes on the next visit, which is a far better trade than
 * making the reader wait: every string is a separate provider call, so a first
 * visit to a fresh screen used to spend the whole page load translating.
 */
const FILL_LIMIT = 60;

/** A string the provider refuses to answer is not worth asking about again. */
const MAX_ATTEMPTS = 5;

const memory = new Map<string, string>();

/**
 * The dictionary's own hand-written pairs, read first.
 *
 * They are kept apart from `memory` so the cache the browser stores stays the
 * machine-made part — the words a catalogue edit or a provider answer actually
 * produced — rather than repeating four hundred entries every visit.
 */
const handWritten = new Map(Object.entries(handWrittenArabic()));

const attempts = new Map<string, number>();
let catalogVersion: string | null = null;
let loaded = false;
let ready = false;

/** One fill per page view, however many mutations follow it. */
let filled = false;
let fillScheduled = false;

/** Keep one catalog request in flight while several screens mount at once. */
let catalogRequest: Promise<void> | null = null;
let versionCheck: Promise<void> | null = null;

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
/** What a string already reads as, from the catalog or from the dictionary. */
function arabicFor(value: string): string | undefined {
    return memory.get(value) ?? handWritten.get(value);
}

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

/** Reads the dictionary the browser stored on its last visit. */
function load(): void {
    if (loaded || typeof window === 'undefined') return;
    loaded = true;

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) return;

        const stored = JSON.parse(raw) as {
            version?: string;
            translations?: Record<string, string>;
        };

        catalogVersion = typeof stored.version === 'string' ? stored.version : null;

        for (const [english, arabic] of Object.entries(stored.translations ?? {})) {
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
            JSON.stringify({ version: catalogVersion, translations: Object.fromEntries(memory) }),
        );
    } catch {
        // Storage full or blocked: the translations still work for this visit.
    }
}

/**
 * Takes a dictionary from the server — with the page, or from the endpoint.
 *
 * Returns whether it wrote anything, so the caller can skip a redundant repaint.
 */
export function applyCatalog(catalog: InterfaceCatalog | null | undefined): boolean {
    if (!catalog || typeof catalog !== 'object') return false;

    load();

    const translations = catalog.translations ?? {};
    const fresh = typeof catalog.version === 'string' && catalog.version !== catalogVersion;
    const empty = memory.size === 0;

    catalogVersion = typeof catalog.version === 'string' ? catalog.version : catalogVersion;

    for (const [english, arabic] of Object.entries(translations)) {
        memory.set(english, arabic);
    }

    save();

    return fresh || empty || Object.keys(translations).length > 0;
}

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

            const arabic = arabicFor(value);

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

            const arabic = arabicFor(value);

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
 * Asks the server for the unseen strings on this screen, once.
 *
 * A single request for the whole screenful: the server answers what its clock
 * allows and hands back the rest as `pending`, which the next visit picks up.
 * The page is already on screen and fully interactive throughout — this is a
 * top-up, not a page load.
 */
async function fillMissing(): Promise<void> {
    if (!ready || filled) return;

    const missing = apply(document.body).filter((value) => (attempts.get(value) ?? 0) < MAX_ATTEMPTS);

    if (missing.length === 0) return;

    filled = true;

    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    try {
        const response = await fetch(FILL_ENDPOINT, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ strings: missing.slice(0, FILL_LIMIT) }),
        });

        const payload = (await response.json().catch(() => ({}))) as {
            translations?: Record<string, string>;
            pending?: string[];
        };

        const answered = Object.entries(payload.translations ?? {});

        if (answered.length > 0) {
            for (const [english, arabic] of answered) {
                memory.set(english, arabic);
            }

            save();
            apply(document.body);

            return;
        }

        // Nothing came back at all. Once the provider has refused a string a few
        // times, leave it in readable English rather than asking for ever.
        const stalled = new Set(payload.pending ?? []);

        for (const value of missing) {
            if (!stalled.has(value) && arabicFor(value) === undefined) {
                attempts.set(value, (attempts.get(value) ?? 0) + 1);
            }
        }
    } catch {
        // Offline, or the request was cut short: the English stays readable.
    }
}

function scheduleFill(): void {
    if (typeof window === 'undefined' || filled || fillScheduled) return;

    fillScheduled = true;

    const run = (): void => {
        fillScheduled = false;
        void fillMissing();
    };

    const idle = window.requestIdleCallback;

    // After paint, when the browser has nothing better to do. The timeout is a
    // floor, not a deadline: a busy page still gets its top-up.
    if (typeof idle === 'function') idle(run, { timeout: 2000 });
    else window.setTimeout(run, 300);
}

/** Writes are debounced: a screenful of React updates is one pass, not fifty. */
function schedule(): void {
    if (typeof window === 'undefined') return;

    if (debounce !== null) window.clearTimeout(debounce);

    debounce = window.setTimeout(() => {
        debounce = null;
        apply(document.body);

        // A screen that renders its content late — a lazy chunk, a fetched
        // table — only becomes translatable once it is on screen, so the top-up
        // is armed here as well as on mount.
        scheduleFill();
    }, 150);
}

/**
 * Fetches the dictionary when the page did not bring it.
 *
 * Normally the browser is handed the catalog with the page, so this is the
 * safety net: a cleared cache, blocked storage, or a version the server said had
 * moved on. It never touches the provider.
 */
function fetchCatalog(): Promise<void> {
    if (catalogRequest) return catalogRequest;

    catalogRequest = (async () => {
        try {
            const response = await fetch(CATALOG_ENDPOINT, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            applyCatalog((await response.json().catch(() => null)) as InterfaceCatalog | null);
            apply(document.body);
        } catch {
            // The strings already in memory still stand.
        }
    })().finally(() => {
        catalogRequest = null;
    });

    return catalogRequest;
}

/**
 * Checks whether the dictionary moved on, and refetches it if it did.
 *
 * A translation edited in the settings screen has to reach the browser without a
 * hard refresh, and a stale dictionary has to be dropped rather than layered on
 * top of the new one.
 */
function syncCatalogVersion(): Promise<void> {
    if (versionCheck) return versionCheck;

    versionCheck = (async () => {
        try {
            const response = await fetch(VERSION_ENDPOINT, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const payload = (await response.json().catch(() => ({}))) as { version?: string };
            const nextVersion = payload.version;

            if (!nextVersion || nextVersion === catalogVersion) return;

            restore();
            memory.clear();
            attempts.clear();
            catalogVersion = nextVersion;
            save();

            await fetchCatalog();
        } catch {
            // Keep the last usable cache when the version endpoint is unavailable.
        }
    })().finally(() => {
        versionCheck = null;
    });

    return versionCheck;
}

/**
 * Starts translating the page copy, and stops when the locale is not Arabic.
 *
 * `catalog` is the dictionary the page arrived with, if it arrived with one.
 * Returns the teardown for the effect that owns it.
 */
export function translateInterfaceCopy(locale: Locale, catalog?: InterfaceCatalog | null): () => void {
    if (typeof window === 'undefined' || locale !== 'ar') {
        return () => undefined;
    }

    load();

    // Both of these run inside the layout effect that calls this, so the known
    // Arabic is in place before the browser paints: no English flash.
    const stored = memory.size > 0;

    if (catalog) applyCatalog(catalog);
    apply(document.body);

    let active = true;

    observer = new MutationObserver(schedule);
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });

    const settle = (): void => {
        void syncCatalogVersion().finally(() => {
            if (!active) return;

            ready = true;
            apply(document.body);
            scheduleFill();
        });
    };

    // A new screen is a new chance to top up, without repeating a refill of the
    // strings an earlier screen on the same visit already took care of.
    const removeNavigateListener = router.on('navigate', () => {
        filled = false;
        settle();
    });

    window.addEventListener('focus', settle);

    // The server stops sending the dictionary once the browser has it, so a
    // missing payload is the normal case — the version check covers it. The
    // fetch is only for a browser that holds nothing at all.
    if (catalog || stored) settle();
    else void fetchCatalog().finally(settle);

    return () => {
        active = false;
        ready = false;
        filled = false;
        fillScheduled = false;
        removeNavigateListener();
        window.removeEventListener('focus', settle);
        observer?.disconnect();
        observer = null;

        if (debounce !== null) {
            window.clearTimeout(debounce);
            debounce = null;
        }

        restore();
    };
}
