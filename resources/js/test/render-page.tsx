import { type ReactElement } from 'react';
import { render, type RenderResult } from '@testing-library/react';
import { LocaleProvider } from '@/lib/i18n/locale-context';
import { ThemeProvider } from '@/lib/theme';

type Locale = 'en' | 'ar';

/**
 * Every page is rendered inside the locale and theme providers in
 * `resources/js/app.tsx`, so tests mount them the same way instead of bare —
 * a bare mount trips `useLocale must be used inside a <LocaleProvider>` in any
 * component that reads the locale, which is most of the shell.
 */
export function renderPage(ui: ReactElement, locale: Locale = 'en'): RenderResult {
    return render(
        <LocaleProvider initialLocale={locale}>
            <ThemeProvider initialMode="light" palettes={null} website={null}>
                {ui}
            </ThemeProvider>
        </LocaleProvider>,
    );
}
