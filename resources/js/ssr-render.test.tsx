// @vitest-environment node
import { describe, expect, it } from 'vitest';
import type { Page } from '@inertiajs/core';
import { renderPage } from '@/ssr-render';

/**
 * The project renders on both sides of the wire, so any page that reaches for
 * `window` while rendering breaks the server only — where Inertia's own failure
 * mode is a silent fallback to client-side rendering. Nothing else in this suite
 * would notice, so this file runs under the Node environment: `window` and
 * `document` are genuinely absent, exactly as they are in `inertia:start-ssr`.
 */
function page(component: string, props: Record<string, unknown>): Page {
    return {
        component,
        props: { ...props, locale: 'en' },
        url: `/${component}`,
        version: '1',
    } as unknown as Page;
}

describe('the server renderer', () => {
    it('renders a page to html, with its head, without a browser', async () => {
        const response = await renderPage(page('auth/login', { errors: {} }));

        expect(response.body).toContain('Sign in to your account');
        expect(response.body).toContain('name="password"');
        expect(response.head.join('')).toContain('Login');
    });

    it('renders in the locale the request asked for', async () => {
        const response = await renderPage({
            ...page('auth/login', { errors: {} }),
            props: { errors: {}, locale: 'ar' },
        } as unknown as Page);

        expect(response.body).toContain('تسجيل الدخول إلى حسابك');
    });
});
