import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { LocaleProvider } from '@/lib/i18n/locale-context';
import type { Locale } from '@/lib/i18n/copy';
import '../css/app.css';

type AppearanceProps = {
    theme?: 'light' | 'dark' | 'system';
    primary_color?: string;
    secondary_color?: string;
    logo_path?: string | null;
    favicon_path?: string | null;
};

function initialLocale(props: unknown): Locale {
    const shared = (props as { locale?: string }).locale;
    return shared === 'ar' ? 'ar' : 'en';
}

function applyAppearance(
    theme: 'light' | 'dark' | 'system',
    branding?: {
        primary_color?: string;
        secondary_color?: string;
        favicon_path?: string | null;
    } | null,
) {
    const root = document.documentElement;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = theme === 'dark' || (theme === 'system' && prefersDark);

    root.classList.toggle('dark', isDark);
    root.dataset.theme = isDark ? 'dark' : 'light';

    // Set branding colors
    if (branding?.primary_color) {
        root.style.setProperty('--color-primary', branding.primary_color);
        // Calculate and set appropriate foreground color for primary
        const primaryForeground = getContrastingColor(branding.primary_color);
        root.style.setProperty('--color-primary-foreground', primaryForeground);
    }

    if (branding?.secondary_color) {
        root.style.setProperty('--color-secondary', branding.secondary_color);
        // Calculate and set appropriate foreground color for secondary
        const secondaryForeground = getContrastingColor(branding.secondary_color);
        root.style.setProperty('--color-secondary-foreground', secondaryForeground);
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

// Helper function to get contrasting color (black or white) for better readability
function getContrastingColor(hexColor: string): string {
    const cleanHex = hexColor.replace('#', '');
    const r = parseInt(cleanHex.substring(0, 2), 16);
    const g = parseInt(cleanHex.substring(2, 4), 16);
    const b = parseInt(cleanHex.substring(4, 6), 16);

    const channel = (value: number) => {
        const normalized = value / 255;
        return normalized <= 0.03928
            ? normalized / 12.92
            : ((normalized + 0.055) / 1.055) ** 2.4;
    };
    const luminance = 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
    const contrastWithWhite = 1.05 / (luminance + 0.05);
    const contrastWithBlack = (luminance + 0.05) / 0.05;

    return contrastWithWhite >= contrastWithBlack ? '#ffffff' : '#000000';
}

function applyAppearanceFromProps(appearance?: AppearanceProps) {
    applyAppearance(appearance?.theme ?? 'system', {
        primary_color: appearance?.primary_color,
        secondary_color: appearance?.secondary_color,
        favicon_path: appearance?.favicon_path,
    });
}

createInertiaApp({
    title: (title) => `${title} - Al Noor School`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')) as never,
    setup({ el, App, props }) {
        const initialProps = props.initialPage.props as {
            appearance?: AppearanceProps;
        };
        applyAppearanceFromProps(initialProps.appearance);

        router.on('success', (event) => {
            const pageProps = event.detail.page.props as {
                appearance?: AppearanceProps;
            };
            applyAppearanceFromProps(pageProps.appearance);
        });

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
