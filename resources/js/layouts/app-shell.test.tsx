import { describe, expect, it, vi } from 'vitest';
import { cleanup, screen } from '@testing-library/react';
import { renderPage } from '@/test/render-page';
import AppShell from './app-shell';

/**
 * A rejected save used to be invisible on every screen except a dozen settings
 * pages: the controller redirected back with validation errors and the page
 * displayed nothing, so a form that failed looked identical to one that saved
 * nothing. The shell now reports it once for the whole app.
 */
const pageProps = vi.hoisted(() => ({
    current: {} as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
    router: { post: () => {}, get: () => {}, visit: () => {}, reload: () => {}, on: () => {} },
    usePage: () => ({
        url: '/finance/invoices/create',
        component: 'finance/invoices/create',
        props: {
            locale: 'en',
            auth: { user: { id: 7, name: 'Registrar', roles: ['registrar'] } },
            ...pageProps.current,
        },
    }),
}));

function mountShell() {
    return renderPage(
        <AppShell title="Page">
            <p>page content</p>
        </AppShell>,
    );
}

describe('app shell form feedback', () => {
    it('reports validation failures on any page, not only the ones that ask for it', () => {
        pageProps.current = { errors: { invoice_number: 'This invoice number is already in use.' } };

        mountShell();

        const alert = screen.getByRole('alert');
        expect(alert.textContent).toContain('Some fields need attention');
        expect(alert.textContent).toContain('invoice number');
        expect(alert.textContent).toContain('already in use');
    });

    it('reports a flashed failure', () => {
        pageProps.current = { flash: { error: 'That application has already been converted.' } };

        mountShell();

        expect(screen.getByRole('alert').textContent).toContain('already been converted');
    });

    it('stays out of the way when nothing failed', () => {
        pageProps.current = {};

        mountShell();

        expect(screen.queryByRole('alert')).toBeNull();
        expect(screen.getByText('page content')).not.toBeNull();
    });

    it('leaves the success confirmation to the page', () => {
        // Pages put their confirmation next to the button that saved, so the
        // shell must not add a second one at the top.
        pageProps.current = { flash: { success: 'Saved.' } };

        mountShell();

        expect(screen.queryByRole('status')).toBeNull();
    });
});

describe('navigation authorization', () => {
    it('hides administrative links when a role has no matching grants', () => {
        pageProps.current = { auth: { user: { id: 7, name: 'Admin', roles: ['school_admin'], permissions: [] } } };
        const { container } = mountShell();
        expect(container.querySelector('a[href="/settings/school"]')).toBeNull();
        expect(container.querySelector('a[href="/announcements"]')).toBeNull();
        expect(container.querySelector('a[href="/students"]')).toBeNull();
    });

    it('offers granted modules for custom roles and removes them after revocation', () => {
        pageProps.current = { auth: { user: { id: 7, name: 'Auditor', roles: ['custom'], permissions: ['view-dashboard', 'manage-payments'] } } };
        const { container } = mountShell();
        expect(container.querySelector('a[href="/finance/payments"]')).not.toBeNull();
        expect(container.querySelector('a[href="/finance/invoices"]')).toBeNull();
        pageProps.current = { auth: { user: { id: 7, name: 'Auditor', roles: ['custom'], permissions: ['view-dashboard'] } } };
        cleanup();
        const revoked = mountShell();
        expect(revoked.container.querySelector('a[href="/finance/payments"]')).toBeNull();
    });
});
