import { forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface TableProps extends React.TableHTMLAttributes<HTMLTableElement> {}

export const Table = forwardRef<HTMLTableElement, TableProps>(({ className, ...props }, ref) => {
    return (
        <div className="w-full overflow-auto">
            <table ref={ref} className={cn('w-full caption-bottom text-sm', className)} {...props} />
        </div>
    );
});
Table.displayName = 'Table';

export const TableHeader = forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(
    ({ className, ...props }, ref) => {
        return <thead ref={ref} className={cn('bg-muted/50 [&_tr]:border-b', className)} {...props} />;
    }
);
TableHeader.displayName = 'TableHeader';

export const TableBody = forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(
    ({ className, ...props }, ref) => {
        return <tbody ref={ref} className={cn('[&_tr:last-child]:border-0', className)} {...props} />;
    }
);
TableBody.displayName = 'TableBody';

export const TableRow = forwardRef<HTMLTableRowElement, React.HTMLAttributes<HTMLTableRowElement>>(
    ({ className, ...props }, ref) => {
        return (
            <tr
                ref={ref}
                className={cn(
                    'border-b transition-colors hover:bg-muted/40 data-[state=selected]:bg-muted/60',
                    className
                )}
                {...props}
            />
        );
    }
);
TableRow.displayName = 'TableRow';

export const TableHead = forwardRef<HTMLTableCellElement, React.ThHTMLAttributes<HTMLTableCellElement>>(
    ({ className, ...props }, ref) => {
        return (
            <th
                ref={ref}
                className={cn(
                    'h-11 px-4 align-middle text-sm font-medium uppercase tracking-[0] text-muted-foreground [&:has([role=checkbox])]:pe-0',
                    className
                )}
                {...props}
            />
        );
    }
);
TableHead.displayName = 'TableHead';

export const TableCell = forwardRef<HTMLTableCellElement, React.TdHTMLAttributes<HTMLTableCellElement>>(
    ({ className, ...props }, ref) => {
        return (
            <td
                ref={ref}
                className={cn('p-3.5 align-middle text-sm font-medium [&:has([role=checkbox])]:pe-0', className)}
                {...props}
            />
        );
    }
);
TableCell.displayName = 'TableCell';
