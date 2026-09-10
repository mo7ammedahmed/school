import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function FinancePaymentsIndex({ payments }: { payments: { id: number; payment_number: string; invoice: { invoice_number: string }; amount: number; payment_method: string; status: string; payment_date: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'payment_number',
            header: 'Payment #',
        },
        {
            accessorKey: 'invoice.invoice_number',
            header: 'Invoice',
        },
        {
            accessorKey: 'amount',
            header: 'Amount',
            cell: ({ row }) => Number(row.original.amount).toFixed(2),
        },
        {
            accessorKey: 'payment_method',
            header: 'Method',
            cell: ({ row }) => row.original.payment_method.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'completed' ? 'default' : status === 'failed' ? 'destructive' : 'secondary';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            accessorKey: 'payment_date',
            header: 'Date',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/payments/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/payments/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Payments"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Payments' },
            ]}
        >
            <PageHeader
                title="Payments"
                description="Track all payments"
                actions={
                    <Button asChild>
                        <Link href="/finance/payments/create"><Plus className="mr-2 h-4 w-4" />New Payment</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={payments} />
        </AppShell>
    );
}
