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
    it('renders managed public content, Arabic sections and private preview without browser APIs', async () => {
        const response = await renderPage({
            ...page('public/page', {}),
            props: {
                locale: 'ar',
                auth: { user: null },
                flash: {},
                errors: {},
                preview: true,
                editorUrl: '/content/pages/1/edit',
                websiteNavigation: [],
                collections: { faq: [{ title: 'School FAQ replaced', description: 'Automatic question' }] },
                page: {
                    title: 'School life',
                    title_ar: 'الحياة المدرسية',
                    slug: 'school-life',
                    sections: [
                        {
                            type: 'hero',
                            enabled: true,
                            content: { title_ar: 'نتعلم ونكتشف' },
                            settings: {},
                        },
                        {
                            type: 'faq',
                            enabled: true,
                            content: {
                                items: [
                                    {
                                        title_ar: 'كيف أتقدم؟',
                                        description_ar: 'قدّم طلبًا إلكترونيًا.',
                                    },
                                ],
                            },
                            settings: {},
                        },
                        {
                            type: 'cta',
                            enabled: false,
                            content: { title: 'Hidden content' },
                            settings: {},
                        },
                    ],
                },
            },
        } as unknown as Page);
        expect(response.body).toContain('نتعلم ونكتشف');
        expect(response.body).toContain('كيف أتقدم؟');
        expect(response.body).toContain('معاينة خاصة');
        // Inertia includes the input fixture as JSON; this assertion checks
        // rendered markup. The feature tests verify server-side filtering.
        const markup = response.body.replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '');
        expect(markup).not.toContain('Hidden content');
        expect(markup).not.toContain('School FAQ replaced');
    }, 15000);
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
