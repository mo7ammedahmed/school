import type { Locale } from './copy';
import { useLocale } from './locale-context';

/**
 * Reads a bilingual pair the way the reader asked for it.
 *
 * Pages wrote `title ?? title_ar` by hand, which shows an Arabic reader the
 * English side whenever English happens to be filled — the exact thing the
 * second column exists to prevent. The decision lives here now, and it falls
 * back to whichever side actually has a value rather than rendering a blank
 * cell for a record that is only translated one way.
 */
export function pickBilingual(
    locale: Locale,
    english?: string | null,
    arabic?: string | null,
    fallback = '—',
): string {
    const en = (english ?? '').trim();
    const ar = (arabic ?? '').trim();
    const preferred = locale === 'ar' ? ar || en : en || ar;

    return preferred || fallback;
}

/** The same decision, bound to the language the reader is in today. */
export function useBilingual(): (english?: string | null, arabic?: string | null, fallback?: string) => string {
    const { locale } = useLocale();

    return (english?: string | null, arabic?: string | null, fallback?: string) =>
        pickBilingual(locale, english, arabic, fallback);
}
