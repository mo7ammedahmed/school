import { useEffect, useRef, useState } from 'react';
import { Loader2, Languages } from 'lucide-react';
import { cn } from '@/lib/utils';

type Locale = 'en' | 'ar';
type AutoTranslation = { source: string; translation: string };

const AUTO_TRANSLATE_DELAY = 1000;

type TranslatePairProps = {
    /** id of the English input */
    enId: string;
    /** id of the Arabic input */
    arId: string;
    /** Render only one direction. Defaults to both. */
    directions?: Array<{ from: Locale; to: Locale }>;
    persist?: { table: string; id: number; enColumn: string; arColumn: string };
    className?: string;
};

/**
 * Writes a value into an input the way React expects.
 *
 * The entity forms in this app are plain HTML forms, but a few use controlled
 * Inertia inputs. Going through the native value setter and dispatching `input`
 * works for both: uncontrolled inputs simply receive the value, and controlled
 * ones fire their onChange so React state stays in step.
 */
function setInputValue(id: string, value: string): boolean {
    const element = document.getElementById(id) as HTMLInputElement | HTMLTextAreaElement | null;

    if (!element) return false;

    const prototype =
        element instanceof HTMLTextAreaElement ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;

    if (setter) {
        setter.call(element, value);
    } else {
        element.value = value;
    }

    element.dispatchEvent(new Event('input', { bubbles: true }));

    return true;
}

function readInputValue(id: string): string {
    const element = document.getElementById(id) as HTMLInputElement | HTMLTextAreaElement | null;

    return element?.value?.trim() ?? '';
}

async function requestTranslation(
    text: string,
    from: Locale,
    to: Locale,
    signal?: AbortSignal,
): Promise<string> {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const cookie = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1];

    const response = await fetch('/translate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token || decodeURIComponent(cookie ?? ''),
        },
        credentials: 'same-origin',
        signal,
        body: JSON.stringify({ text, from, to }),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Translation failed.');
    }

    return String(payload.translation ?? '');
}

async function saveTranslation(
    table: string,
    id: number,
    sourceColumn: string,
    sourceValue: string,
    column: string,
    value: string,
    signal?: AbortSignal,
): Promise<string> {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const cookie = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1];
    const response = await fetch('/translate/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token || decodeURIComponent(cookie ?? ''),
        },
        credentials: 'same-origin',
        signal,
        body: JSON.stringify({
            table,
            id,
            source_column: sourceColumn,
            source_value: sourceValue,
            column,
            value,
        }),
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Could not save the translation.');
    }

    return String(payload.value ?? value);
}

/**
 * Makes a bilingual pair accept either language.
 *
 * Forms used to mark the English field `required`, which quietly made the app
 * English-first: a school that types Arabic could not save at all, even though
 * the server accepts either side and translates the other. Neither field is
 * required on its own — the pair only needs one value, so the guard reports an
 * error on the English field (the one the operator is most likely to fill last)
 * when both are blank.
 *
 * It sets that message and nothing else. It used to force `required = false` on
 * both fields on every keystroke, which is not this component's business: those
 * attributes belong to whoever wrote the form, and a guard that clears them can
 * hide a genuinely required field. `BilingualFormsTest` is what keeps the pair
 * from being marked English-required in the first place.
 */
function useEitherLanguageGuard(enId: string, arId: string): void {
    useEffect(() => {
        const english = document.getElementById(enId) as HTMLInputElement | HTMLTextAreaElement | null;
        const arabic = document.getElementById(arId) as HTMLInputElement | HTMLTextAreaElement | null;

        if (!english || !arabic) return;

        const EN_MESSAGE = 'Fill in one language — the other one is translated for you.';

        const revalidate = (): void => {
            english.setCustomValidity(
                english.value.trim() === '' && arabic.value.trim() === '' ? EN_MESSAGE : '',
            );
        };

        revalidate();

        // `change` as well as `input`: a value filled by the browser's autofill
        // or dropped in by paste does not always raise a key event.
        english.addEventListener('input', revalidate);
        arabic.addEventListener('input', revalidate);
        english.addEventListener('change', revalidate);
        arabic.addEventListener('change', revalidate);

        return () => {
            english.removeEventListener('input', revalidate);
            arabic.removeEventListener('input', revalidate);
            english.removeEventListener('change', revalidate);
            arabic.removeEventListener('change', revalidate);

            // Leave the field as we found it: the message is ours, and a node
            // that outlives this component must not keep reporting our error.
            english.setCustomValidity('');
        };
    }, [enId, arId]);
}

/**
 * Fills one language field from the other using the school's translation
 * provider. Kept next to the fields it drives so the operator can see what will
 * be overwritten (nothing — only the empty side is filled unless they press the
 * button themselves).
 */
export function TranslatePair({
    enId,
    arId,
    directions = [
        { from: 'en', to: 'ar' },
        { from: 'ar', to: 'en' },
    ],
    persist,
    className,
}: TranslatePairProps) {
    const [busy, setBusy] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [done, setDone] = useState<string | null>(null);
    const autoApplied = useRef(new Map<string, AutoTranslation>());
    const persistRef = useRef(persist);
    persistRef.current = persist;

    useEitherLanguageGuard(enId, arId);

    useEffect(() => {
        const timers = new Map<string, number>();
        const controllers = new Map<string, AbortController>();

        const translateAfterTypingStops = (event: Event): void => {
            const field = event.target;
            if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) return;

            const from: Locale | null = field.id === enId ? 'en' : field.id === arId ? 'ar' : null;
            if (!from) return;

            const to: Locale = from === 'en' ? 'ar' : 'en';
            const sourceId = from === 'en' ? enId : arId;
            const targetId = to === 'en' ? enId : arId;
            const key = `${from}-${to}`;
            const source = readInputValue(sourceId);
            const existingTimer = timers.get(key);

            if (existingTimer !== undefined) window.clearTimeout(existingTimer);
            controllers.get(key)?.abort();
            controllers.delete(key);
            timers.delete(key);

            if (source.length < 2) return;

            const previous = autoApplied.current.get(key);
            const currentTarget = readInputValue(targetId);

            // Keep a manually entered translation untouched.
            if (currentTarget && currentTarget !== previous?.translation) return;

            timers.set(key, window.setTimeout(() => {
                timers.delete(key);
                if (readInputValue(sourceId) !== source) return;

                const targetBeforeRequest = readInputValue(targetId);
                const latestAuto = autoApplied.current.get(key);
                if (targetBeforeRequest && targetBeforeRequest !== latestAuto?.translation) return;

                const controller = new AbortController();
                controllers.set(key, controller);
                setBusy(key);
                setError(null);

                void requestTranslation(source, from, to, controller.signal)
                    .then(async (translation) => {
                        if (controller.signal.aborted || readInputValue(sourceId) !== source) return;

                        const targetNow = readInputValue(targetId);
                        const autoValue = autoApplied.current.get(key)?.translation;
                        if (targetNow && targetNow !== autoValue) return;

                        let value = translation;
                        const saveTarget = persistRef.current;

                        if (saveTarget) {
                            value = await saveTranslation(
                                saveTarget.table,
                                saveTarget.id,
                                from === 'en' ? saveTarget.enColumn : saveTarget.arColumn,
                                source,
                                to === 'en' ? saveTarget.enColumn : saveTarget.arColumn,
                                translation,
                                controller.signal,
                            );
                        }

                        if (controller.signal.aborted || readInputValue(sourceId) !== source) return;

                        const finalTarget = readInputValue(targetId);
                        if (finalTarget && finalTarget !== autoApplied.current.get(key)?.translation) return;

                        autoApplied.current.set(key, { source, translation: value });
                        setInputValue(targetId, value);
                        setDone(key);
                        window.setTimeout(() => setDone(null), 2500);
                    })
                    .catch((e: unknown) => {
                        if (!controller.signal.aborted) {
                            setError(e instanceof Error ? e.message : 'Translation failed.');
                        }
                    })
                    .finally(() => {
                        if (controllers.get(key) === controller) {
                            controllers.delete(key);
                            setBusy((current) => current === key ? null : current);
                        }
                    });
            }, AUTO_TRANSLATE_DELAY));
        };

        const english = document.getElementById(enId);
        const arabic = document.getElementById(arId);

        english?.addEventListener('input', translateAfterTypingStops);
        arabic?.addEventListener('input', translateAfterTypingStops);

        return () => {
            english?.removeEventListener('input', translateAfterTypingStops);
            arabic?.removeEventListener('input', translateAfterTypingStops);
            timers.forEach((timer) => window.clearTimeout(timer));
            controllers.forEach((controller) => controller.abort());
            controllers.clear();
        };
    }, [enId, arId]);

    const run = async (from: Locale, to: Locale) => {
        const sourceId = from === 'en' ? enId : arId;
        const targetId = to === 'en' ? enId : arId;
        const text = readInputValue(sourceId);
        const key = `${from}-${to}`;

        setError(null);
        setDone(null);

        if (!text) {
            setError(`Nothing to translate — fill the ${from.toUpperCase()} field first.`);
            return;
        }

        setBusy(key);

        try {
            const translation = await requestTranslation(text, from, to);
            const value = persist
                ? await saveTranslation(
                    persist.table,
                    persist.id,
                    from === 'en' ? persist.enColumn : persist.arColumn,
                    text,
                    to === 'en' ? persist.enColumn : persist.arColumn,
                    translation,
                )
                : translation;

            if (!setInputValue(targetId, value)) {
                setError('Could not find the target field.');
            } else {
                autoApplied.current.set(key, { source: text, translation: value });
                setDone(key);
                setTimeout(() => setDone(null), 2500);
            }
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Translation failed.');
        } finally {
            setBusy(null);
        }
    };

    return (
        <div className={cn('mt-2', className)}>
            <div className="flex flex-wrap items-center gap-2">
                <Languages className="h-3.5 w-3.5 text-muted-foreground" aria-hidden="true" />
                {directions.map(({ from, to }) => {
                    const key = `${from}-${to}`;
                    const isBusy = busy === key;

                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => void run(from, to)}
                            disabled={busy !== null}
                            className={cn(
                                'inline-flex items-center gap-1 rounded-md border border-border/70 bg-card px-2 py-1',
                                'text-xs font-medium text-muted-foreground transition-colors',
                                'hover:border-border hover:text-foreground disabled:opacity-50',
                            )}
                        >
                            {isBusy ? (
                                <Loader2 className="h-3 w-3 animate-spin" />
                            ) : (
                                <Languages className="h-3 w-3" />
                            )}
                            {done === key ? 'Done' : `${from.toUpperCase()} → ${to.toUpperCase()}`}
                        </button>
                    );
                })}
                <span className="text-xs text-muted-foreground">
                    the empty language fills after you pause typing
                </span>
            </div>
            {error && <p className="mt-1.5 text-xs text-destructive">{error}</p>}
        </div>
    );
}
