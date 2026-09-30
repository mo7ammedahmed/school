import { type ComponentProps } from 'react';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderPage } from '@/test/render-page';
import AdmissionsReviewShow from './show';

/**
 * The review screen's decision form used to be a plain `<form method="POST">`
 * with its submit buttons in the page header and a `decision` radio inside the
 * form. The header button sent `decision=rejected` and the checked radio sent
 * `decision=approved`; PHP keeps the last value for a repeated field name, so
 * pressing "Reject" approved the application.
 *
 * These tests pin the contract at the point where it broke: what the decision
 * request actually carries.
 */
const h = vi.hoisted(() => ({
    posts: [] as { url: string; data: Record<string, unknown> }[],
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
    router: { post: () => {}, get: () => {}, visit: () => {}, reload: () => {} },
    usePage: () => ({
        url: '/admissions/review/1',
        component: 'admissions/review/show',
        props: {
            locale: 'en',
            auth: { user: { id: 7, name: 'Registrar', roles: ['registrar'] } },
        },
    }),
    useForm: (initial: Record<string, unknown>) => {
        const state: {
            data: Record<string, unknown>;
            transform: ((data: Record<string, unknown>) => Record<string, unknown>) | null;
        } = { data: { ...initial }, transform: null };

        return {
            get data() {
                return state.data;
            },
            processing: false,
            setData: (key: string, value: unknown) => {
                state.data[key] = value;
            },
            transform: (callback: (data: Record<string, unknown>) => Record<string, unknown>) => {
                state.transform = callback;
            },
            post: (url: string) => {
                h.posts.push({
                    url,
                    data: state.transform ? state.transform(state.data) : { ...state.data },
                });
            },
        };
    },
}));

type ApplicationProp = ComponentProps<typeof AdmissionsReviewShow>['application'];

const baseApplication: ApplicationProp = {
    id: 42,
    reference: 'APP-100042',
    status: 'submitted',
    priority: 'medium',
    student_first_name: 'Nour',
    student_last_name: 'Rahman',
    guardian_first_name: 'Amal',
    guardian_last_name: 'Rahman',
    guardian_email: 'amal@example.test',
    documents: [],
    events: [],
    created_at: '2026-09-01T09:00:00Z',
    updated_at: '2026-09-01T09:00:00Z',
};

function application(overrides: Partial<ApplicationProp> = {}): ApplicationProp {
    return { ...baseApplication, ...overrides };
}

describe('admissions review decision', () => {
    beforeEach(() => {
        h.posts.length = 0;
    });

    it('sends a rejection when the reviewer rejects', () => {
        renderPage(<AdmissionsReviewShow application={application()} />);

        fireEvent.click(screen.getAllByRole('button', { name: /reject/i })[0]);

        expect(h.posts).toHaveLength(1);
        expect(h.posts[0].url).toBe('/admissions/applications/42/decide');
        expect(h.posts[0].data.decision).toBe('rejected');
    });

    it('sends an approval when the reviewer approves', () => {
        renderPage(<AdmissionsReviewShow application={application()} />);

        fireEvent.click(screen.getAllByRole('button', { name: /approve/i })[0]);

        expect(h.posts).toHaveLength(1);
        expect(h.posts[0].data.decision).toBe('approved');
    });

    it('carries the notes the reviewer typed with the decision', () => {
        const { container } = renderPage(<AdmissionsReviewShow application={application()} />);

        const notes = container.querySelector('#review_notes');
        if (!(notes instanceof HTMLTextAreaElement)) {
            throw new Error('The decision card no longer has a #review_notes field.');
        }

        fireEvent.change(notes, { target: { value: 'No place in this grade' } });
        fireEvent.click(screen.getAllByRole('button', { name: /reject/i })[0]);

        expect(h.posts).toHaveLength(1);
        expect(h.posts[0].data).toMatchObject({
            decision: 'rejected',
            notes: 'No place in this grade',
        });
    });

    it('never funnels the decision through a native form', () => {
        const { container } = renderPage(<AdmissionsReviewShow application={application()} />);

        // A `decision` field inside a native form is what made "Reject" submit
        // "approved": the header button and the checked radio both named it.
        expect(container.querySelectorAll('input[name="decision"], select[name="decision"]')).toHaveLength(0);
        expect(container.querySelector('#decision-form')).toBeNull();

        const posting = Array.from(container.querySelectorAll('form')).filter((form) =>
            (form.getAttribute('action') ?? '').includes('/decide'),
        );
        expect(posting).toHaveLength(0);
    });

    it('hides the decision controls once a decision exists', () => {
        renderPage(<AdmissionsReviewShow application={application({ status: 'approved' })} />);

        expect(screen.queryByRole('button', { name: /reject/i })).toBeNull();
        expect(screen.queryByRole('button', { name: /approve/i })).toBeNull();
    });
});
