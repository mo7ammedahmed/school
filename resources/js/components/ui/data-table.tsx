import * as React from 'react';
import {
    useReactTable,
    getCoreRowModel,
    getSortedRowModel,
    flexRender,
    type ColumnDef,
    type SortingState,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DataTableProps<TData> {
    columns: ColumnDef<TData, any>[];
    data: TData[] | { data: TData[] };
    className?: string;
    emptyMessage?: string;
    onRowClick?: (row: TData) => void;
}

export function DataTable<TData extends Record<string, any>>({
    columns,
    data,
    className,
    emptyMessage = 'No records found.',
    onRowClick,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = React.useState<SortingState>([]);

    // Accept both plain arrays and Laravel paginator payloads ({ data: [...] }).
    const rows = Array.isArray(data) ? data : data?.data ?? [];

    const table = useReactTable({
        data: rows,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
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
                                                'px-4 py-3 text-start text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-muted-foreground',
                                                canSort &&
                                                    'cursor-pointer select-none transition-colors hover:text-foreground'
                                            )}
                                            onClick={canSort ? header.column.getToggleSortingHandler() : undefined}
                                            aria-sort={
                                                sorted === 'asc'
                                                    ? 'ascending'
                                                    : sorted === 'desc'
                                                      ? 'descending'
                                                      : 'none'
                                            }
                                        >
                                            <span className="inline-flex items-center gap-1">
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(header.column.columnDef.header, header.getContext())}
                                                {canSort &&
                                                    (sorted === 'asc' ? (
                                                        <ArrowUp className="size-3 text-foreground" aria-hidden="true" />
                                                    ) : sorted === 'desc' ? (
                                                        <ArrowDown className="size-3 text-foreground" aria-hidden="true" />
                                                    ) : (
                                                        <ChevronsUpDown className="size-3 opacity-50" aria-hidden="true" />
                                                    ))}
                                            </span>
                                        </th>
                                    );
                                })}
                            </tr>
                        ))}
                    </thead>
                    <tbody className="divide-y divide-border/70 bg-card">
                        {table.getRowModel().rows.length === 0 ? (
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
                                        <td key={cell.id} className="px-4 py-3 text-sm text-foreground/90">
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
