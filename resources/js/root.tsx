import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ResolvedComponent } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { Locale } from '@/lib/i18n/copy';
import { LocaleProvider } from '@/lib/i18n/locale-context';
import type { RawThemeModes, ThemeMode } from '@/lib/theme';
import { normalisePalettes, ThemeProvider } from '@/lib/theme';

/**
 * What the two entries (`app.tsx` in the browser, `ssr.tsx` under
 * `inertia:start-ssr`) have to agree on.
 *
 * They are separate bundles with separate globals, so anything they spell twice
 * can drift — and a provider tree that differs between the server's HTML and the
 * browser's first render is worse than no SSR at all: React hydrates nothing,
 * discards the markup and re-renders the page, which is the blank flash SSR was
 * switched on to remove. Both entries render through `InertiaRoot` and resolve
 * pages through `resolvePage`, so there is one tree and one name-to-file rule.
 */

type AppearanceProps = {
    theme?: ThemeMode;
    primary_color?: string | null;
    secondary_color?: string | null;
    accent_color?: string | null;
    logo_path?: string | null;
    favicon_path?: string | null;
};

export type RootProps = {
    appearance?: AppearanceProps;
    themeModes?: RawThemeModes | null;
    /** The school's website colours, derived from its few chosen colours. */
    themeConfig?: Record<string, string> | null;
    auth?: { user?: unknown | null };
};

/**
 * The page a controller named, resolved the one way both entries agree on:
 * `./Pages/{name}.tsx`, with test files excluded from the glob — every file the
 * glob matches becomes a lazily-loaded page, so a `.test.tsx` would ship the
 * test libraries to production.
 */
export function resolvePage(name: string): Promise<ResolvedComponent> {
    return resolvePageComponent(
        `./Pages/${name}.tsx`,
        import.meta.glob(['./Pages/**/*.tsx', '!./Pages/**/*.test.tsx']),
    ) as Promise<ResolvedComponent>;
}

export function initialLocale(props: unknown): Locale {
    const shared = (props as { locale?: string }).locale;
    return shared === 'ar' ? 'ar' : 'en';
}

export function InertiaRoot({ children, pageProps }: { children: ReactNode; pageProps: RootProps }) {
    return (
        <LocaleProvider initialLocale={initialLocale(pageProps)}>
            <ThemeProvider
                initialMode={pageProps.appearance?.theme ?? 'system'}
                palettes={normalisePalettes(pageProps.themeModes)}
                website={pageProps.themeConfig}
            >
                {children}
            </ThemeProvider>
        </LocaleProvider>
    );
}
