import { useState } from 'react';
import { Loader2, Languages } from 'lucide-react';
import { cn } from '@/lib/utils';

type Locale = 'en' | 'ar';

type TranslatePairProps = {
    /** id of the English input */
    enId: string;
    /** id of the Arabic input */
    arId: string;
    /** Render only one direction. Defaults to both. */
    directions?: Array<{ from: Locale; to: Locale }>;
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
    element.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
}

function readInputValue(id: string): string {
    const element = document.getElementById(id) as HTMLInputElement | HTMLTextAreaElement | null;

    return element?.value?.trim() ?? '';
}

async function requestTranslation(text: string, from: Locale, to: Locale): Promise<string> {
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
        body: JSON.stringify({ text, from, to }),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Translation failed.');
    }

    return String(payload.translation ?? '');
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
    className,
}: TranslatePairProps) {
    const [busy, setBusy] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [done, setDone] = useState<string | null>(null);

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

            if (!setInputValue(targetId, translation)) {
                setError('Could not find the target field.');
            } else {
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
                    or leave the other language empty and it is filled on save
                </span>
            </div>
            {error && <p className="mt-1.5 text-xs text-destructive">{error}</p>}
        </div>
    );
}
