import { type ComponentProps } from 'react';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderPage } from '@/test/render-page';
import { DataTable } from './data-table';

/**
 * `DataTable` accepts a Laravel paginator and renders its `data` array. Until now
 * that was the whole of its understanding of one: the pager lived on whichever
 * screens had remembered to add one.
 *
 * Roughly forty screens pass a paginated list straight into this component, and
 * the ones that had not added a pager showed fifteen rows of a longer list with
 * nothing to indicate the rest existed. The rows after the first page were
 * unreachable, which is a different and worse bug than a slow screen.
 *
 * So the pager belongs here, next to the code that decides what one page is.
 * These tests pin the contract: a paginator gets a pager, an array does not, a
 * single page does not need one, and paging asks the server for the rows rather
 * than slicing rows the browser was never sent.
 */
const h = vi.hoisted(() => ({
    gets: [] as { url: string }[],
    url: '/teachers?status=active&page=1',
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
    router: {
        get: (url: string) => h.gets.push({ url }),
        post: () => {},
        visit: () => {},
        reload: () => {},
    },
    usePage: () => ({
        url: h.url,
        component: 'teachers/index',
        props: { locale: 'en', auth: { user: { id: 7, name: 'Registrar', roles: [] } } },
    }),
}));

interface Row {
    id: number;
    name: string;
}

const columns: ComponentProps<typeof DataTable<Row>>['columns'] = [
    { accessorKey: 'name', header: 'Name' },
];

function rows(count: number, from = 1): Row[] {
    return Array.from({ length: count }, (_, index) => ({ id: from + index, name: `Row ${from + index}` }));
}

/** A Laravel paginator payload, as `paginate()` serialises to. */
function paginator(overrides: Record<string, unknown> = {}) {
    return {
        data: rows(15),
        current_page: 1,
        last_page: 7,
        total: 100,
        from: 1,
        to: 15,
        ...overrides,
    };
}

describe('DataTable pagination', () => {
    beforeEach(() => {
        h.gets.length = 0;
        h.url = '/teachers?status=active&page=1';
    });

    it('offers the pages after the first when handed a paginator', () => {
        renderPage(<DataTable columns={columns} data={paginator() as never} />);

        fireEvent.click(screen.getByRole('button', { name: '2' }));

        expect(h.gets).toHaveLength(1);
        expect(h.gets[0].url).toContain('page=2');
    });

    it('reports how many rows exist in total', () => {
        renderPage(<DataTable columns={columns} data={paginator() as never} />);

        // The whole point: a truncated list that looks complete is the failure.
        expect(screen.getByText('Showing 1–15 of 100')).toBeTruthy();
    });

    it('renders no pager for a plain array', () => {
        renderPage(<DataTable columns={columns} data={rows(15)} />);

        expect(screen.queryByRole('navigation', { name: /pagination/i })).toBeNull();
    });

    it('renders no pager when everything fits on one page', () => {
        renderPage(
            <DataTable
                columns={columns}
                data={paginator({ data: rows(4), last_page: 1, total: 4, from: 1, to: 4 }) as never}
            />
        );

        expect(screen.queryByRole('navigation', { name: /pagination/i })).toBeNull();
    });

    it('renders no pager for an empty result', () => {
        renderPage(
            <DataTable
                columns={columns}
                data={paginator({ data: [], last_page: 1, total: 0, from: null, to: null }) as never}
            />
        );

        expect(screen.queryByRole('navigation', { name: /pagination/i })).toBeNull();
    });

    it('keeps the current query so a filter survives paging', () => {
        // Page two of the *unfiltered* list is a screen answering a question
        // nobody asked, and it is the mistake a hand-written pager makes.
        renderPage(<DataTable columns={columns} data={paginator() as never} />);

        // Page seven, not page three: with seven pages the pager renders
        // 1, 2, …, 7, so three is not a button to click.
        fireEvent.click(screen.getByRole('button', { name: '7' }));

        expect(h.gets[0].url).toBe('/teachers?status=active&page=7');
    });

    it('pages back to the first page rather than sending page=1', () => {
        h.url = '/teachers?status=active&page=3';

        renderPage(<DataTable columns={columns} data={paginator({ current_page: 3 }) as never} />);

        fireEvent.click(screen.getByRole('button', { name: '1' }));

        // Page one is the absence of the parameter; `page=1` is a second URL for
        // the same list, and links that accumulate it are how a canonical URL
        // ends up with seven variants of itself.
        expect(h.gets[0].url).toBe('/teachers?status=active');
    });

    it('marks the current page for assistive technology', () => {
        renderPage(<DataTable columns={columns} data={paginator({ current_page: 2 }) as never} />);

        expect(screen.getByRole('button', { current: 'page' }).textContent).toBe('2');
    });
});
