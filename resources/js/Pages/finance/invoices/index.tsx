import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { formatCurrency } from '@/lib/utils';

interface Invoice {
    id: number;
    invoice_number: string;
    student?: { first_name: string; last_name: string };
    issue_date: string;
    due_date: string;
    total_amount: number;
    balance_due: number;
    currency: string;
    status: string;
}

export default function FinanceInvoicesIndex({ invoices }: { invoices: Invoice[] }) {
    const columns: ColumnDef<Invoice, any>[] = [
        {
            accessorKey: 'invoice_number',
            header: 'Invoice #',
        },
        {
            id: 'student',
            header: 'Student',
            accessorFn: (row) =>
                row.student ? `${row.student.first_name} ${row.student.last_name}` : '—',
        },
        {
            accessorKey: 'issue_date',
            header: 'Issue Date',
        },
        {
            accessorKey: 'due_date',
            header: 'Due Date',
        },
        {
            accessorKey: 'total_amount',
            header: 'Total',
            cell: ({ row }) => formatCurrency(Number(row.original.total_amount), row.original.currency),
        },
        {
            accessorKey: 'balance_due',
            header: 'Balance Due',
            cell: ({ row }) => formatCurrency(Number(row.original.balance_due), row.original.currency),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant =
                    status === 'paid'
                        ? 'success'
                        : status === 'issued'
                          ? 'info'
                          : status === 'partially_paid'
                            ? 'warning'
                            : status === 'voided'
                              ? 'destructive'
                              : 'secondary';
                return <Badge variant={variant}>{status.replace('_', ' ')}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/invoices/${row.original.id}`}>View</Link>
                    </Button>
                    {row.original.status === 'draft' && (
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={`/finance/invoices/${row.original.id}/edit`}>Edit</Link>
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Invoices"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Invoices' },
            ]}
        >
            <PageHeader
                title="Invoices"
                description="Manage your invoices"
                actions={
                    <Button asChild>
                        <Link href="/finance/invoices/create">
                            <Plus className="mr-2 h-4 w-4" />
                            New Invoice
                        </Link>
                    </Button>
                }
            />

            <DataTable
                columns={columns}
                data={invoices}
                emptyMessage="No invoices yet. Create your first invoice to get started."
            />
        </AppShell>
    );
}
