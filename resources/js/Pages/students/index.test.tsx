import { type ComponentProps } from 'react';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderPage } from '@/test/render-page';
import StudentsIndex from './index';

/**
 * `StudentController::index` paginates at 15 and hands the paginator straight
 * to this page. `DataTable` reads a paginator's `data` array and renders those
 * rows — and nothing else. So a school with two hundred students was shown
 * fifteen of them, with no pager, no count, and nothing on the screen to say the
 * list had been cut off.
 *
 * The rest of the roll was not missing from the database; it was unreachable
 * from the only screen that lists students. These tests pin the two halves of
 * the fix: that the pager is there, and that it asks the server for the page
 * rather than trying to slice rows the browser was never sent.
 */
const h = vi.hoisted(() => ({
    gets: [] as { url: string; params: Record<string, unknown> }[],
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
    router: {
        get: (url: string, params: Record<string, unknown>) => h.gets.push({ url, params }),
        post: () => {},
        visit: () => {},
        reload: () => {},
    },
    usePage: () => ({
        url: '/students',
        component: 'students/index',
        props: {
            locale: 'en',
            auth: { user: { id: 7, name: 'Registrar', roles: ['registrar'] } },
        },
    }),
}));

type Student = ComponentProps<typeof StudentsIndex>['students']['data'][number];

function student(index: number): Student {
    return {
        id: index,
        first_name: `Pupil${index}`,
        last_name: 'Surname',
        email: `pupil${index}@example.test`,
        student_id_number: `STU-${String(index).padStart(4, '0')}`,
        status: 'active',
    };
}

/** A Laravel paginator payload, as `paginate(15)` serialises to. */
function page(currentPage: number, lastPage: number, total: number, rowCount: number) {
    return {
        data: Array.from({ length: rowCount }, (_, index) => student(currentPage * 100 + index)),
        current_page: currentPage,
        last_page: lastPage,
        total,
        from: rowCount === 0 ? null : (currentPage - 1) * 15 + 1,
        to: rowCount === 0 ? 0 : (currentPage - 1) * 15 + rowCount,
    };
}

describe('students list pagination', () => {
    beforeEach(() => {
        h.gets.length = 0;
    });

    it('offers a way to reach the students after the first fifteen', () => {
        renderPage(<StudentsIndex students={page(1, 7, 100, 15) as never} />);

        // Page two is the whole question: if it cannot be asked, the other
        // eighty-five students do not exist as far as this screen is concerned.
        fireEvent.click(screen.getByRole('button', { name: '2' }));

        expect(h.gets).toHaveLength(1);
        expect(h.gets[0].url).toBe('/students');
        expect(h.gets[0].params.page).toBe(2);
    });

    it('says how many students there are, so a truncated list is visible', () => {
        renderPage(<StudentsIndex students={page(1, 7, 100, 15) as never} />);

        // Matched exactly: a loose `/100/` also matches the enrolment numbers and
        // email addresses in the table, which would pass on a screen with no
        // count at all.
        expect(screen.getByText('Showing 1–15 of 100')).toBeTruthy();
    });

    it('renders no pager when every student fits on one page', () => {
        renderPage(<StudentsIndex students={page(1, 1, 9, 9) as never} />);

        expect(screen.queryByRole('navigation', { name: /pagination/i })).toBeNull();
    });

    it('sends no page request from the first page', () => {
        renderPage(<StudentsIndex students={page(1, 7, 100, 15) as never} />);

        expect(h.gets).toHaveLength(0);
    });

    it('shows an empty list without a pager when a school has no students', () => {
        renderPage(<StudentsIndex students={page(1, 1, 0, 0) as never} />);

        expect(screen.queryByRole('navigation', { name: /pagination/i })).toBeNull();
    });
});
