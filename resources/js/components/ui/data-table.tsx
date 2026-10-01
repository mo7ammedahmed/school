import * as React from 'react';
import { flexRender, useTable, type RowData, type SortingState } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { tableFeatureSet, type ColumnDef } from '@/lib/table';
import { Pagination } from '@/components/ui/pagination';
import { useLocale } from '@/lib/i18n/locale-context';
import { t } from '@/lib/i18n/copy';

/**
 * The fields of a Laravel paginator that this component needs.
 *
 * Exported because a page that receives one should say so. Six screens used to
 * declare their list prop as `{ data: T[] }` — the shape they needed — which
 * type-checked while hiding the fact that the prop was a paginator with counts
 * and a pager attached.
 */
export interface Paginator<TData> {
    data: TData[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

function isPaginator<TData extends RowData>(data: TData[] | Paginator<TData>): data is Paginator<TData> {
    return !Array.isArray(data) && typeof (data as Paginator<TData>)?.last_page === 'number';
}

interface DataTableProps<TData extends RowData> {
    columns: ColumnDef<TData, any>[];
    data: TData[] | Paginator<TData>;
    className?: string;
    emptyMessage?: string;
    loading?: boolean;
    errorMessage?: string;
    onRowClick?: (row: TData) => void;
}

export function DataTable<TData extends RowData>({
    columns,
    data,
    className,
    emptyMessage = 'No records found.',
    loading = false,
    errorMessage,
    onRowClick,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const { locale } = useLocale();

    // Accept both plain arrays and Laravel paginator payloads ({ data: [...] }).
    const page = isPaginator(data) ? data : null;
    const rows = Array.isArray(data) ? data : data?.data ?? [];

    /**
     * Paging keeps whatever is already in the address bar.
     *
     * The list on screen was narrowed by whatever query produced it, so the
     * next page has to be asked for under the same conditions. Dropping them
     * turns "page 2" into a different question than the one that was on screen —
     * the mistake a hand-written pager makes, and the reason the filters are
     * read back off the current URL rather than passed down as props that every
     * one of the forty-odd call sites would have to remember to forward.
     */
    const { url: currentUrl } = usePage();
    const goToPage = React.useCallback(
        (target: number) => {
            const [pathname, search = ''] = currentUrl.split('?');
            const params = new URLSearchParams(search);

            // Page one is the absence of the parameter. `page=1` is a
            // different URL for the same list, and links that accumulate it are
            // how a canonical URL ends up with seven variants of itself.
            if (target <= 1) {
                params.delete('page');
            } else {
                params.set('page', String(target));
            }

            const query = params.toString();

            router.get(pathname + (query === '' ? '' : `?${query}`), {}, { preserveScroll: true, preserveState: true });
        },
        [currentUrl]
    );

    const table = useTable({
        features: tableFeatureSet,
        data: rows,
        columns,
        state: { sorting },
        onSortingChange: setSorting,
    });

    const totalRows = page?.total ?? rows.length;
    const showPager = page !== null && !loading && page.last_page > 1;

    return (
        <div className={cn('space-y-4', className)}>
            <div className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[var(--shadow-sm)]">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-border/80">
                    <thead className="bg-muted/50">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    const canSort = header.column.getCanSort();
                                    const sorted = header.column.getIsSorted();
                                    return (
                                        <th
                                            key={header.id}
                                            scope="col"
                                            className={cn(
                                                'px-4 py-3 text-start text-sm font-medium uppercase tracking-[0] leading-[1.4] text-muted-foreground',
                                                canSort &&
                                                    'cursor-pointer select-none transition-colors hover:text-foreground'
                                            )}
                                            aria-sort={
                                                sorted === 'asc'
                                                    ? 'ascending'
                                                    : sorted === 'desc'
                                                      ? 'descending'
                                                      : 'none'
                                            }
                                        >
                                            {header.isPlaceholder ? null : (
                                                <button
                                                    type="button"
                                                    className="inline-flex items-center gap-1 text-start focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60"
                                                    onClick={canSort ? header.column.getToggleSortingHandler() : undefined}
                                                    disabled={!canSort}
                                                    aria-label={canSort ? `Sort by ${String(header.column.columnDef.header)}` : undefined}
                                                >
                                                    {flexRender(header.column.columnDef.header, header.getContext())}
                                                    {canSort &&
                                                        (sorted === 'asc' ? (
                                                            <ArrowUp className="size-3 text-foreground" aria-hidden="true" />
                                                        ) : sorted === 'desc' ? (
                                                            <ArrowDown className="size-3 text-foreground" aria-hidden="true" />
                                                        ) : (
                                                            <ChevronsUpDown className="size-3 opacity-50" aria-hidden="true" />
                                                        ))}
                                                </button>
                                            )}
                                        </th>
                                    );
                                })}
                            </tr>
                        ))}
                    </thead>
                    <tbody className="divide-y divide-border/70 bg-card">
                        {loading ? (
                            <tr>
                                <td colSpan={table.getAllFlatColumns().length} className="px-4 py-16 text-center">
                                    <div className="mx-auto h-4 w-32 animate-pulse rounded-full bg-muted" aria-label="Loading" />
                                </td>
                            </tr>
                        ) : errorMessage ? (
                            <tr>
                                <td colSpan={columns.length} className="px-4 py-16 text-center text-sm text-destructive">
                                    {errorMessage}
                                </td>
                            </tr>
                        ) : table.getRowModel().rows.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length} className="px-4 py-16 text-center">
                                    <p className="text-sm text-muted-foreground">{emptyMessage}</p>
                                </td>
                            </tr>
                        ) : (
                            table.getRowModel().rows.map((row) => (
                                <tr
                                    key={row.id}
                                    className={cn(
                                        'transition-colors hover:bg-muted/40',
                                        onRowClick && 'cursor-pointer'
                                    )}
                                    onClick={onRowClick ? () => onRowClick(row.original) : undefined}
                                >
                                    {row.getAllCells().map((cell) => (
                                        <td key={cell.id} className="px-4 py-3 text-sm font-medium leading-[1.4] text-foreground/90">
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
            </div>

            {/*
              The pager sits here, next to the code that decides what one page
              is, rather than on each screen. A list that is cut at fifteen rows
              and gives no sign of it reads as a complete list, so the count is
              shown whether or not there is anywhere to page to.
            */}
            {page !== null && !loading && totalRows > 0 && (
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-muted-foreground">
                        {t(locale, 'common.showingRange', {
                            from: page.from ?? 0,
                            to: page.to ?? 0,
                            total: totalRows,
                        })}
                    </p>
                    {showPager && (
                        <Pagination
                            pageCount={page.last_page}
                            currentPage={page.current_page}
                            previousLabel={t(locale, 'common.previous')}
                            nextLabel={t(locale, 'common.next')}
                            onPageChange={goToPage}
                        />
                    )}
                </div>
            )}
        </div>
    );
}
