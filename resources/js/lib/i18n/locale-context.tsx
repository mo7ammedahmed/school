import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
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

    const value: LocaleContextValue = {
        locale,
        rtl,
        setLocale,
        toggleLocale: () => setLocale((prev) => (prev === 'ar' ? 'en' : 'ar')),
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