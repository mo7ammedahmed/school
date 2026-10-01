import { createRoot, hydrateRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { InertiaRoot, resolvePage, type RootProps } from '@/root';
import {
    applyTheme,
    getContrastingColor,
    normalisePalettes,
    readStoredMode,
    setServerPersistence,
} from '@/lib/theme';
import '../css/app.css';

/** The branding block of the shared props, spelled once — in `@/root`. */
type AppearanceProps = NonNullable<RootProps['appearance']>;

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

function applyAppearanceFromProps(props: RootProps): void {
    applyBranding(props.appearance);
    // The public site is browsable signed-out: keep the light/dark choice local
    // in that case instead of posting a preference nobody owns yet.
    setServerPersistence(Boolean(props.auth?.user));
    // A choice made in this browser wins over the (possibly stale) server value,
    // otherwise the next navigation would undo an in-flight toggle.
    const mode = readStoredMode() ?? props.appearance?.theme ?? 'system';
    // The website palette goes in last, so the school's own colours win wherever
    // the two name the same variable.
    applyTheme(mode, normalisePalettes(props.themeModes), props.themeConfig);
}

createInertiaApp({
    resolve: resolvePage,

    setup({ el, App, props }) {
        installCsrfTokens();

        const initialProps = props.initialPage.props as RootProps;
        applyAppearanceFromProps(initialProps);

        router.on('success', (event) => {
            applyAppearanceFromProps(event.detail.page.props as RootProps);
        });

        const tree = (
            <InertiaRoot pageProps={initialProps}>
                <App {...props} />
            </InertiaRoot>
        );

        // With the SSR server running, Laravel hands over markup that React
        // already produced. Hydrating reuses it; mounting would throw it away
        // and re-render the page in the browser, which is the flash SSR exists
        // to remove. Without SSR the attribute is absent and this is a mount.
        if (el.hasAttribute('data-server-rendered')) {
            hydrateRoot(el, tree);
        } else {
            createRoot(el).render(tree);
        }
    },

    progress: {
        color: '#046A38',
    },

    serverHead: true,
});
