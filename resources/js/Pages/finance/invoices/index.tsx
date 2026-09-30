import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/ui/data-table';
import { formatCurrency } from '@/lib/utils';
import { type ColumnDef } from '@/lib/table';
import { Link, router } from '@inertiajs/react';
import { Download, FileDown, Plus, Send } from 'lucide-react';
import { useLocale } from '@/lib/i18n/locale-context';
import { t } from '@/lib/i18n/copy';
import { invoiceStatusLabel, invoiceStatusVariant } from '@/lib/finance/invoice-status';

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

const channelLabel = (channels: Record<string, boolean>) => {
    const used = Object.keys(channels ?? {}).filter((key) => channels[key]);
    if (used.length === 0) return null;
    return used
        .map((key) => (key === 'inapp' ? 'in-app' : key))
        .join(' · ');
};

export default function FinanceInvoicesIndex({ invoices }: { invoices: Invoice[] }) {
    const { locale } = useLocale();

    const send = (invoice: Invoice) => {
        router.post(`/finance/invoices/${invoice.id}/send`, {}, { preserveScroll: true });
    };

    const issue = (invoice: Invoice) => {
        router.post(`/finance/invoices/${invoice.id}/issue`, {}, { preserveScroll: true });
    };

    const columns: ColumnDef<Invoice, any>[] = [
        {
            accessorKey: 'invoice_number',
            header: t(locale, 'finance.invoices.number'),
            cell: ({ row }) => (
                <Link href={`/finance/invoices/${row.original.id}`} className="font-medium hover:underline">
                    {row.original.invoice_number}
                </Link>
            ),
        },
        {
            id: 'student',
            header: t(locale, 'finance.invoices.student'),
            accessorFn: (row) =>
                row.student ? `${row.student.first_name} ${row.student.last_name}` : '—',
        },
        {
            accessorKey: 'due_date',
            header: t(locale, 'finance.invoices.due'),
        },
        {
            accessorKey: 'total_amount',
            header: t(locale, 'finance.invoices.total'),
            cell: ({ row }) => formatCurrency(Number(row.original.total_amount), row.original.currency),
        },
        {
            accessorKey: 'balance_due',
            header: t(locale, 'finance.invoices.balance'),
            cell: ({ row }) => formatCurrency(Number(row.original.balance_due), row.original.currency),
        },
        {
            accessorKey: 'status',
            header: t(locale, 'finance.invoices.status'),
            cell: ({ row }) => (
                <Badge variant={invoiceStatusVariant(row.original.status)}>
                    {invoiceStatusLabel(locale, row.original.status)}
                </Badge>
            ),
        },
        {
            id: 'delivery',
            header: t(locale, 'finance.invoices.sentToGuardian'),
            cell: ({ row }) => {
                const channels = channelLabel(row.original.delivery_channels);
                if (!row.original.sent_at) {
                    return <span className="text-muted-foreground">{t(locale, 'finance.invoices.notSent')}</span>;
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
            header: t(locale, 'common.actions'),
            cell: ({ row }) => {
                const invoice = row.original;
                const settled = invoice.status === 'paid';

                return (
                    <div className="flex flex-wrap gap-1">
                        {invoice.status === 'draft' && (
                            <Button variant="ghost" size="sm" onClick={() => issue(invoice)}>
                                <Send className="mr-1.5 h-3.5 w-3.5" />
                                {t(locale, 'finance.invoices.issue')}
                            </Button>
                        )}
                        {!settled && (
                            <Button variant="ghost" size="sm" onClick={() => send(invoice)}>
                                <Send className="mr-1.5 h-3.5 w-3.5" />
                                {t(locale, invoice.sent_at ? 'finance.invoices.resend' : 'finance.invoices.send')}
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
            title={t(locale, 'finance.invoices.title')}
            breadcrumbs={[
                { label: t(locale, 'nav.dashboard'), href: '/dashboard' },
                { label: t(locale, 'nav.finance'), href: '/finance/invoices' },
                { label: t(locale, 'finance.invoices.title') },
            ]}
        >
            <PageHeader
                title={t(locale, 'finance.invoices.title')}
                description={t(locale, 'finance.invoices.description')}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/payments/offline">
                                <FileDown className="mr-2 h-4 w-4" />
                                {t(locale, 'finance.invoices.confirmPayments')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/finance/invoices/create">
                                <Plus className="mr-2 h-4 w-4" />
                                {t(locale, 'finance.invoices.new')}
                            </Link>
                        </Button>
                    </div>
                }
            />

            <DataTable
                columns={columns}
                data={invoices}
                emptyMessage={t(locale, 'finance.invoices.empty')}
            />
        </AppShell>
    );
}
