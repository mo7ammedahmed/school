import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { LocaleProvider } from '@/lib/i18n/locale-context';
import type { Locale } from '@/lib/i18n/copy';
import '../css/app.css';

function initialLocale(props: unknown): Locale {
    const shared = (props as { locale?: string }).locale;
    return shared === 'ar' ? 'ar' : 'en';
}

createInertiaApp({
    title: (title) => `${title} — Al Noor School`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')) as never,
    setup({ el, App, props }) {
        createRoot(el).render(
            <LocaleProvider initialLocale={initialLocale(props.initialPage.props)}>
                <App {...props} />
            </LocaleProvider>,
        );
    },
    progress: {
        color: '#046A38',
    },
});
