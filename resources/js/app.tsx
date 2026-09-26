import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { LocaleProvider } from '@/lib/i18n/locale-context';
import type { Locale } from '@/lib/i18n/copy';
import {
    DEFAULT_PALETTES,
    ThemeProvider,
    applyTheme,
    getContrastingColor,
    readStoredMode,
    type Palettes,
    type ThemeMode,
} from '@/lib/theme';
import '../css/app.css';

type AppearanceProps = {
    theme?: ThemeMode;
    primary_color?: string | null;
    secondary_color?: string | null;
    accent_color?: string | null;
    logo_path?: string | null;
    favicon_path?: string | null;
};

type RootProps = {
    appearance?: AppearanceProps;
    themeModes?: Palettes | null;
};

function initialLocale(props: unknown): Locale {
    const shared = (props as { locale?: string }).locale;
    return shared === 'ar' ? 'ar' : 'en';
}

/**
 * Plain `<form method="POST">` elements across the dashboard do not carry a
 * CSRF token, so Laravel rejects them with a 419. Injecting the token on submit
 * fixes every such form at once and leaves Inertia's own requests, which send
 * the token as a header, untouched.
 */
function installCsrfTokens(): void {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) return;
            if ((form.method || 'get').toLowerCase() === 'get') return;
            if (form.querySelector('input[name="_token"]')) return;

            const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

            if (!token) return;

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = token;
            form.appendChild(input);
        },
        true,
    );
}

/**
 * School branding that is *not* part of the light/dark palettes: the logo
 * favicon and the secondary/accent colours. The five headline tokens are
 * owned by lib/theme so the toggle stays consistent.
 */
function applyBranding(branding?: AppearanceProps | null): void {
    const root = document.documentElement;

    if (branding?.secondary_color) {
        root.style.setProperty('--color-secondary', branding.secondary_color);
        root.style.setProperty('--color-secondary-foreground', getContrastingColor(branding.secondary_color));
    }

    if (branding?.accent_color) {
        root.style.setProperty('--color-accent', branding.accent_color);
        root.style.setProperty('--color-accent-foreground', getContrastingColor(branding.accent_color));
    }

    if (branding?.favicon_path) {
        let favicon = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
        if (!favicon) {
            favicon = document.createElement('link');
            favicon.rel = 'icon';
            document.head.appendChild(favicon);
        }
        favicon.href = `/storage/${branding.favicon_path}`;
    }
}

function normalisePalettes(modes?: Palettes | null): Palettes {
    if (!modes?.light || !modes?.dark) return DEFAULT_PALETTES;
    return modes;
}

function applyAppearanceFromProps(props: RootProps): void {
    applyBranding(props.appearance);
    // A choice made in this browser wins over the (possibly stale) server value,
    // otherwise the next navigation would undo an in-flight toggle.
    const mode = readStoredMode() ?? props.appearance?.theme ?? 'system';
    applyTheme(mode, normalisePalettes(props.themeModes));
}

createInertiaApp({
    title: (title) => `${title} - Al Noor School`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')) as never,
    setup({ el, App, props }) {
        installCsrfTokens();

        const initialProps = props.initialPage.props as RootProps;
        applyAppearanceFromProps(initialProps);

        router.on('success', (event) => {
            applyAppearanceFromProps(event.detail.page.props as RootProps);
        });

        createRoot(el).render(
            <LocaleProvider initialLocale={initialLocale(props.initialPage.props)}>
                <ThemeProvider
                    initialMode={initialProps.appearance?.theme ?? 'system'}
                    palettes={normalisePalettes(initialProps.themeModes)}
                >
                    <App {...props} />
                </ThemeProvider>
            </LocaleProvider>,
        );
    },
    progress: {
        color: '#046A38',
    },
});
