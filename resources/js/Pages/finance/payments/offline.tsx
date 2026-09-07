import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function FinanceOfflinePayments({ payments }: { payments: { id: number; invoice: { invoice_number: string }; amount: number; payment_method: string; reference_number: string; status: string; payment_date: string }[] }) {
    const columns: ColumnDef<any>[] = [
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
            accessorKey: 'reference_number',
            header: 'Reference',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => row.original.status,
        },
        {
            accessorKey: 'payment_date',
            header: 'Date',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <Button variant="ghost" size="sm" asChild>
                    <Link href={`/finance/payments/${row.original.id}/return`}>Process</Link>
                </Button>
            ),
        },
    ];

    return (
        <AppShell
            title="Offline Payments"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Payments', href: '/finance/payments' },
                { label: 'Offline Payments' },
            ]}
        >
            <PageHeader
                title="Offline Payments"
                description="Manage bank transfer and offline payments"
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
