import createServer from '@inertiajs/react/server';
import { renderPage } from '@/ssr-render';

/**
 * The Server-Side Rendering entry.
 *
 * `php artisan inertia:start-ssr` runs this bundle in a Node process which
 * answers Inertia's render requests with finished HTML; Laravel falls back to
 * client-side rendering when the bundle is absent or the server is unhealthy.
 *
 * Only what is safe without a DOM belongs on this side: the browser's own boot
 * — CSRF injection, `document.documentElement` theming, the favicon — stays in
 * `app.tsx`, and everything the two entries share comes from `@/root`.
 */
createServer((page) => renderPage(page));
