import { useRef, useState } from 'react';

export type BackfillTotals = {
    scanned: number;
    missing: number;
    translated: number;
    failed: number;
    remaining: number;
    en_to_ar: number;
    ar_to_en: number;
};

export type BackfillTarget = {
    label: string;
    scanned: number;
    missing: number;
    translated: number;
    failed: number;
    /** What the server says is still empty after the scan it just made. */
    remaining: number;
    en_to_ar: number;
    ar_to_en: number;
};

/** One table's contribution to the run, summed over every batch. */
export type BackfillAdded = {
    label: string;
    intoArabic: number;
    intoEnglish: number;
};

export type BackfillReport = {
    ok: boolean;
    message: string;
    error: string | null;
    /** The last scan, so "left" is measured after the run rather than before it. */
    targets: BackfillTarget[];
    /** What the run actually added, per table, in both directions. */
    added: BackfillAdded[];
};

const EMPTY_TOTALS: BackfillTotals = {
    scanned: 0,
    missing: 0,
    translated: 0,
    failed: 0,
    remaining: 0,
    en_to_ar: 0,
    ar_to_en: 0,
};

/**
 * How many values one request translates.
 *
 * Every value is its own provider call, so a whole-school sweep in a single
 * request could run for many minutes and left the screen stuck on
 * "Translating…". The run is therefore walked in small batches, each one short
 * enough to finish comfortably, with the progress reported between them.
 */
const BACKFILL_BATCH = 10;

export const BACKFILL_ENDPOINT = '/settings/translations/backfill';

/** POST helper for the JSON actions on the translation screens. */
export async function postJson(
    url: string,
    body?: Record<string, unknown>,
    signal?: AbortSignal,
): Promise<{ ok: boolean; status: number; payload: Record<string, unknown> }> {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
        },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
        signal,
    });

    const payload = (await response.json().catch(() => ({}))) as Record<string, unknown>;

    return { ok: response.ok, status: response.status, payload };
}

/** Totals as the server reports them, with every key present. */
function totalsFrom(payload: Record<string, unknown>): BackfillTotals {
    return { ...EMPTY_TOTALS, ...((payload.totals ?? {}) as Partial<BackfillTotals>) };
}

function targetsFrom(payload: Record<string, unknown>): BackfillTarget[] {
    return (Array.isArray(payload.targets) ? (payload.targets as BackfillTarget[]) : []).map((target) => ({
        ...target,
        // Older payloads have no `remaining`; the difference is the same number.
        remaining: target.remaining ?? Math.max(0, target.missing - target.translated - target.failed),
    }));
}

/** Adds one payload's per-table counts onto the running totals for the run. */
function accumulate(added: Map<string, BackfillAdded>, targets: BackfillTarget[]): void {
    for (const target of targets) {
        const entry = added.get(target.label) ?? { label: target.label, intoArabic: 0, intoEnglish: 0 };

        entry.intoArabic += target.en_to_ar;
        entry.intoEnglish += target.ar_to_en;
        added.set(target.label, entry);
    }
}

/**
 * Runs the "translate everything that is missing" sweep, in batches.
 *
 * The loop used to live inside the settings page, tangled with its layout: a
 * thousand-line screen where the run's clock, its abort handling and the wording
 * of its report sat between two pieces of JSX. It is a hook now, so the page is
 * a page — and the run is exercisable on its own.
 *
 * The button doubles as the stop button: while a sweep is in flight, pressing it
 * again aborts the request in progress rather than starting a second run that
 * would fight the first over the same rows.
 */
export function useTranslationSweep(endpoint: string = BACKFILL_ENDPOINT) {
    const [backfilling, setBackfilling] = useState(false);
    const [report, setReport] = useState<BackfillReport | null>(null);
    const [progress, setProgress] = useState<{ done: number; total: number } | null>(null);
    const abortRef = useRef<AbortController | null>(null);
    // A ref, not state: the guard has to be correct on the very first click,
    // before React has re-rendered the button into its "stop" shape.
    const running = useRef(false);

    const stop = (): void => {
        abortRef.current?.abort();
    };

    const run = async (): Promise<void> => {
        if (running.current) {
            stop();
            return;
        }

        running.current = true;

        const controller = new AbortController();
        abortRef.current = controller;

        setBackfilling(true);
        setReport(null);
        setProgress(null);

        let translated = 0;
        let failed = 0;
        let enToAr = 0;
        let arToEn = 0;
        let backlog = 0;
        let targets: BackfillTarget[] = [];
        let serverError: string | null = null;
        // Every request re-scans from the top, so the per-table numbers in a
        // single response only describe that batch. Summing them over the run is
        // what lets the report say what the run added, per table and direction.
        const added = new Map<string, BackfillAdded>();
        const addedList = (): BackfillAdded[] =>
            [...added.values()].filter((entry) => entry.intoArabic + entry.intoEnglish > 0);

        try {
            // A scan costs nothing: it counts what is missing without calling
            // the provider, so progress can be measured against a real total.
            const scan = await postJson(endpoint, { limit: 0 }, controller.signal);

            if (!scan.ok) {
                setReport({
                    ok: false,
                    message: String(scan.payload.message ?? 'Could not scan for missing translations.'),
                    error: typeof scan.payload.error === 'string' ? scan.payload.error : null,
                    targets: [],
                    added: [],
                });
                return;
            }

            backlog = totalsFrom(scan.payload).missing;
            targets = targetsFrom(scan.payload);
            setProgress({ done: 0, total: backlog });

            // Each batch keeps going while it is making progress; a batch that
            // translates nothing means the provider is refusing, so stop rather
            // than loop forever on the same value. The ceiling is generous
            // because the server also stops on its own clock, which can return
            // fewer values than were asked for.
            const attempts = backlog + 20;

            for (let round = 0; round < attempts; round++) {
                if (backlog - translated - failed <= 0) break;

                const step = await postJson(endpoint, { limit: BACKFILL_BATCH }, controller.signal);
                const totals = totalsFrom(step.payload);

                if (!step.ok) {
                    serverError = String(step.payload.error ?? step.payload.message ?? 'The last batch failed.');
                    targets = targetsFrom(step.payload);
                    failed += totals.failed;
                    break;
                }

                translated += totals.translated;
                failed += totals.failed;
                enToAr += totals.en_to_ar;
                arToEn += totals.ar_to_en;
                targets = targetsFrom(step.payload);
                accumulate(added, targets);
                serverError = typeof step.payload.error === 'string' ? step.payload.error : null;

                setProgress({ done: translated, total: backlog });

                if (step.payload.done === true) break;

                // Nothing translated and something failed: the provider is
                // unhappy, so hand the decision back to the operator.
                if (totals.translated === 0) {
                    serverError ??= 'The translation service did not answer for the values it tried. Check the key, model and provider.';
                    break;
                }
            }

            const remaining = Math.max(0, backlog - translated - failed);
            const directions: string[] = [];
            if (enToAr > 0) directions.push(`${enToAr} into Arabic`);
            if (arToEn > 0) directions.push(`${arToEn} into English`);
            const directionText = directions.length > 0 ? ` (${directions.join(', ')})` : '';

            setProgress({ done: translated, total: backlog });

            setReport({
                ok: serverError === null && remaining === 0,
                message:
                    backlog === 0
                        ? 'Nothing was missing — every record already has both Arabic and English.'
                        : serverError !== null
                          ? `Translated ${translated} of ${backlog}${directionText} before stopping.`
                          : remaining === 0
                            ? `Done — translated ${translated} of ${backlog} missing values${directionText}.`
                            : `Translated ${translated} of ${backlog}${directionText}. ${remaining} left — press again to continue.`,
                error: serverError,
                targets,
                added: addedList(),
            });
        } catch (error) {
            const aborted = error instanceof DOMException && error.name === 'AbortError';
            const remaining = Math.max(0, backlog - translated - failed);

            setReport({
                ok: false,
                message: aborted
                    ? `Stopped. Translated ${translated} of ${backlog}; ${remaining} left.`
                    : 'Could not reach the server. Nothing else was changed.',
                error: aborted ? null : 'The request to the translation service did not complete.',
                targets,
                added: addedList(),
            });
        } finally {
            running.current = false;
            abortRef.current = null;
            setBackfilling(false);
        }
    };

    return { backfilling, report, progress, run, stop };
}
