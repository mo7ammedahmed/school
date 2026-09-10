import * as React from 'react';
import {
    createCoreRowModel,
    createSortedRowModel,
} from '@tanstack/table-core';
import {
    type ColumnDef,
    type SortingState,
    flexRender,
    useTable,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DataTableProps<TData> {
    columns: ColumnDef<TData, any>[];
    data: TData[] | { data: TData[] };
    className?: string;
    emptyMessage?: string;
    loading?: boolean;
    errorMessage?: string;
    onRowClick?: (row: TData) => void;
}

export function DataTable<TData extends Record<string, any>>({
    columns,
    data,
    className,
    emptyMessage = 'No records found.',
    loading = false,
    errorMessage,
    onRowClick,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = React.useState<SortingState>([]);

    // Accept both plain arrays and Laravel paginator payloads ({ data: [...] }).
    const rows = Array.isArray(data) ? data : data?.data ?? [];

    const table = useTable({
        data: rows,
        columns,
        getCoreRowModel: createCoreRowModel(),
        getSortedRowModel: createSortedRowModel(),
        onSortingChange: setSorting,
        state: { sorting },
    });

    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border border-border/80 bg-card shadow-[var(--shadow-sm)]',
                className
            )}
        >
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
                                <td colSpan={table.getVisibleFlatColumns().length} className="px-4 py-16 text-center">
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
                                    {row.getVisibleCells().map((cell) => (
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
    );
}
