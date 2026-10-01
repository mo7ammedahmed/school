import { createInertiaApp } from '@inertiajs/react';
import type { Page } from '@inertiajs/core';
import { renderToString } from 'react-dom/server';
import { InertiaRoot, resolvePage, type RootProps } from '@/root';

/**
 * Renders one page to HTML with no browser present.
 *
 * Split out of `ssr.tsx` — which only wraps this in `createServer` — so that
 * `ssr-render.test.tsx` can exercise the real thing under a Node environment
 * instead of a copy of it: a test that renders a second, hand-written tree
 * would keep passing after the server's tree broke.
 */
export function renderPage(page: Page) {
    return createInertiaApp({
        page,
        render: renderToString,
        resolve: resolvePage,
        setup: ({ App, props }) => (
            <InertiaRoot pageProps={props.initialPage.props as RootProps}>
                <App {...props} />
            </InertiaRoot>
        ),
        serverHead: true,
    });
}
