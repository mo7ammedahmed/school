import { createContext, useContext, useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import type { Locale } from '@/lib/i18n/copy';

const STORAGE_KEY = 'aether.locale';

interface LocaleContextValue {
    locale: Locale;
    rtl: boolean;
    setLocale: (locale: Locale) => void;
    toggleLocale: () => void;
}

const LocaleContext = createContext<LocaleContextValue | null>(null);

function detectInitialLocale(initial: Locale): Locale {
    if (typeof window === 'undefined') {
        return initial;
    }

    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        if (stored === 'en' || stored === 'ar') {
            return stored;
        }
    } catch {
    }

    return document.documentElement.lang === 'ar' ? 'ar' : initial;
}

export function LocaleProvider({
    children,
    initialLocale = 'en',
}: {
    children: ReactNode;
    initialLocale?: Locale;
}) {
    const [locale, setLocale] = useState<Locale>(() => detectInitialLocale(initialLocale));
    const rtl = locale === 'ar';

    useEffect(() => {
        const root = document.documentElement;
        root.lang = locale;
        root.dir = rtl ? 'rtl' : 'ltr';

        try {
            window.localStorage.setItem(STORAGE_KEY, locale);
        } catch {
        }

        return () => {
            root.lang = 'en';
            root.dir = 'ltr';
        };
    }, [locale, rtl]);

    // Keep the server in step. The server renders the bilingual columns
    // (`name_ar` / `name_en`) using its own locale, so a client-only choice
    // leaves Arabic chrome wrapping English data.
    const persisted = useRef(initialLocale);

    const persist = useCallback((next: Locale) => {
        if (persisted.current === next) return;
        persisted.current = next;

        const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

        void fetch('/locale', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ locale: next }),
        })
            .then((response) => {
                // Re-fetch the page so server-rendered names switch language too.
                if (response.ok) {
                    router.reload();
                }
            })
            .catch(() => {
                /* the next full page load will pick up the stored choice */
            });
    }, []);

    // Reconcile once on mount: if this device remembers a different language
    // than the server rendered with, tell the server about it.
    useEffect(() => {
        persist(locale);
    }, [locale, persist]);

    const value: LocaleContextValue = {
        locale,
        rtl,
        setLocale: (next: Locale) => {
            setLocale(next);
            persist(next);
        },
        toggleLocale: () => {
            const next: Locale = locale === 'ar' ? 'en' : 'ar';
            setLocale(next);
            persist(next);
        },
    };

    return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

export function useLocale(): LocaleContextValue {
    const context = useContext(LocaleContext);
    if (!context) {
        throw new Error('useLocale must be used inside a <LocaleProvider>.');
    }
    return context;
}