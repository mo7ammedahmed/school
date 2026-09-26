import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/ui/data-table';
import { formatCurrency } from '@/lib/utils';
import { type ColumnDef } from '@/lib/table';
import { Link, router } from '@inertiajs/react';
import { Download, FileDown, Plus, Send } from 'lucide-react';

interface Invoice {
    id: number;
    invoice_number: string;
    student?: { first_name: string; last_name: string } | null;
    issue_date: string | null;
    due_date: string | null;
    total_amount: number;
    balance_due: number;
    currency: string;
    status: string;
    sent_at: string | null;
    delivery_channels: Record<string, boolean>;
}

const statusVariant = (status: string) => {
    if (status === 'paid') return 'success' as const;
    if (status === 'issued') return 'info' as const;
    if (status === 'partially_paid') return 'warning' as const;
    if (status === 'void') return 'destructive' as const;
    return 'secondary' as const;
};

const channelLabel = (channels: Record<string, boolean>) => {
    const used = Object.keys(channels ?? {}).filter((key) => channels[key]);
    if (used.length === 0) return null;
    return used
        .map((key) => (key === 'inapp' ? 'in-app' : key))
        .join(' · ');
};

export default function FinanceInvoicesIndex({ invoices }: { invoices: Invoice[] }) {
    const send = (invoice: Invoice) => {
        router.post(`/finance/invoices/${invoice.id}/send`, {}, { preserveScroll: true });
    };

    const issue = (invoice: Invoice) => {
        router.post(`/finance/invoices/${invoice.id}/issue`, {}, { preserveScroll: true });
    };

    const columns: ColumnDef<Invoice, any>[] = [
        {
            accessorKey: 'invoice_number',
            header: 'Invoice #',
            cell: ({ row }) => (
                <Link href={`/finance/invoices/${row.original.id}`} className="font-medium hover:underline">
                    {row.original.invoice_number}
                </Link>
            ),
        },
        {
            id: 'student',
            header: 'Student',
            accessorFn: (row) =>
                row.student ? `${row.student.first_name} ${row.student.last_name}` : '—',
        },
        {
            accessorKey: 'due_date',
            header: 'Due',
        },
        {
            accessorKey: 'total_amount',
            header: 'Total',
            cell: ({ row }) => formatCurrency(Number(row.original.total_amount), row.original.currency),
        },
        {
            accessorKey: 'balance_due',
            header: 'Balance',
            cell: ({ row }) => formatCurrency(Number(row.original.balance_due), row.original.currency),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <Badge variant={statusVariant(row.original.status)}>{row.original.status.replace('_', ' ')}</Badge>
            ),
        },
        {
            id: 'delivery',
            header: 'Sent to guardian',
            cell: ({ row }) => {
                const channels = channelLabel(row.original.delivery_channels);
                if (!row.original.sent_at) {
                    return <span className="text-muted-foreground">Not sent</span>;
                }
                return (
                    <div className="text-sm">
                        <div>{new Date(row.original.sent_at).toLocaleDateString()}</div>
                        {channels && <div className="text-xs text-muted-foreground">{channels}</div>}
                    </div>
                );
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => {
                const invoice = row.original;
                const settled = invoice.status === 'paid';

                return (
                    <div className="flex flex-wrap gap-1">
                        {invoice.status === 'draft' && (
                            <Button variant="ghost" size="sm" onClick={() => issue(invoice)}>
                                <Send className="mr-1.5 h-3.5 w-3.5" />
                                Issue
                            </Button>
                        )}
                        {!settled && (
                            <Button variant="ghost" size="sm" onClick={() => send(invoice)}>
                                <Send className="mr-1.5 h-3.5 w-3.5" />
                                {invoice.sent_at ? 'Resend' : 'Send'}
                            </Button>
                        )}
                        <Button variant="ghost" size="sm" asChild>
                            <a href={`/finance/invoices/${invoice.id}/pdf`}>
                                <Download className="mr-1.5 h-3.5 w-3.5" />
                                PDF
                            </a>
                        </Button>
                    </div>
                );
            },
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
                description="Issue an invoice to send the payment link and PDF to the guardian automatically."
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/payments/offline">
                                <FileDown className="mr-2 h-4 w-4" />
                                Confirm payments
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/finance/invoices/create">
                                <Plus className="mr-2 h-4 w-4" />
                                New Invoice
                            </Link>
                        </Button>
                    </div>
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
