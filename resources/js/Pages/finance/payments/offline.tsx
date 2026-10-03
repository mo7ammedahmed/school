import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';

type PendingPayment = {
    id: number;
    invoice: { invoice_number: string } | null;
    student?: { first_name: string; last_name: string } | null;
    amount: number;
    payment_method: string;
    reference_number: string | null;
    status: string;
    payment_date: string;
};

export default function FinanceOfflinePayments({ payments }: { payments: PendingPayment[] }) {
    const columns: ColumnDef<PendingPayment, any>[] = [
        {
            id: 'invoice',
            header: 'Invoice',
            accessorFn: (row) => row.invoice?.invoice_number ?? '—',
        },
        {
            id: 'student',
            header: 'Student',
            accessorFn: (row) =>
                row.student ? `${row.student.first_name} ${row.student.last_name}` : '—',
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
            cell: ({ row }) => row.original.reference_number ?? '—',
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
                    <Link href={`/finance/payments/${row.original.id}/review`}>Confirm</Link>
                </Button>
            ),
        },
    ];

    return (
        <AppShell
            title="Offline Payments"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Payments', href: '/finance/payments' },
                { label: 'Offline Payments' },
            ]}
        >
            <PageHeader
                title="Offline Payments"
                description="Confirm bank transfers a guardian reported from their payment link."
                actions={
                    <Button asChild>
                        <Link href="/finance/payments/create"><Plus className="me-2 h-4 w-4" />New Payment</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={payments} />
        </AppShell>
    );
}
