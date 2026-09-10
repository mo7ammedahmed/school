import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function FinanceRefundsIndex({ refunds }: { refunds: { id: number; amount: number; reason: string; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'id',
            header: 'Refund #',
            cell: ({ row }) => `#${row.original.id}`,
        },
        {
            accessorKey: 'amount',
            header: 'Amount',
            cell: ({ row }) => Number(row.original.amount).toFixed(2),
        },
        {
            accessorKey: 'reason',
            header: 'Reason',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'completed' ? 'default' : status === 'rejected' ? 'destructive' : 'secondary';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/refunds/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/refunds/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Refunds"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Refunds' },
            ]}
        >
            <PageHeader
                title="Refunds"
                description="Process and track refunds"
                actions={
                    <Button asChild>
                        <Link href="/finance/refunds/create"><Plus className="mr-2 h-4 w-4" />New Refund</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={refunds} />
        </AppShell>
    );
}
