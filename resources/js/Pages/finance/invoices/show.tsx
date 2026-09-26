import { useState, type ReactNode } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, Copy, Download, ExternalLink, Mail, MessageSquare, Bell, Send } from 'lucide-react';

type Invoice = {
    id: number;
    invoice_number: string;
    issue_date: string | null;
    due_date: string | null;
    subtotal: number;
    tax_rate: number;
    tax_amount: number;
    discount_amount: number;
    total_amount: number;
    amount_paid: number;
    balance_due: number;
    outstanding: number;
    currency: string;
    status: string;
    notes: string | null;
    student?: { first_name: string; last_name: string; student_number?: string } | null;
    lines?: { id: number; description: string; quantity: number; amount: number }[];
    payments?: { id: number; amount: number; payment_method: string; status: string; payment_date: string }[];
};

type Guardian = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_financial: boolean;
};

type Delivery = {
    sent_at: string | null;
    reminder_sent_at: string | null;
    channels: Record<string, boolean>;
    auto_send: boolean;
};

type Props = {
    invoice: Invoice;
    payUrl: string | null;
    guardians: Guardian[];
    delivery: Delivery;
};

const statusVariant = (status: string) => {
    if (status === 'paid') return 'success' as const;
    if (status === 'issued') return 'info' as const;
    if (status === 'partially_paid') return 'warning' as const;
    if (status === 'void') return 'destructive' as const;
    return 'secondary' as const;
};

const money = (amount: number, currency: string) =>
    `${Number(amount ?? 0).toFixed(2)} ${currency}`;

export default function FinanceInvoicesShow({ invoice, payUrl, guardians, delivery }: Props) {
    const [copied, setCopied] = useState(false);

    const settled = invoice.status === 'paid';
    // Issued invoices are financial documents: void and reissue instead of editing.
    const editable = invoice.status === 'draft' || invoice.status === 'overdue';

    const issue = () => router.post(`/finance/invoices/${invoice.id}/issue`, {}, { preserveScroll: true });
    const send = () => router.post(`/finance/invoices/${invoice.id}/send`, {}, { preserveScroll: true });

    const copyLink = async () => {
        if (!payUrl) return;
        try {
            await navigator.clipboard.writeText(payUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <AppShell
            title="Invoice Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Invoices', href: '/finance/invoices' },
                { label: invoice.invoice_number },
            ]}
        >
            <PageHeader
                title={`Invoice ${invoice.invoice_number}`}
                description={
                    invoice.student
                        ? `${invoice.student.first_name} ${invoice.student.last_name}`
                        : 'Invoice details'
                }
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/invoices">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={`/finance/invoices/${invoice.id}/pdf`}>
                                <Download className="mr-2 h-4 w-4" />
                                PDF
                            </a>
                        </Button>
                        {editable && (
                            <Button variant="outline" asChild>
                                <Link href={`/finance/invoices/${invoice.id}/edit`}>Edit</Link>
                            </Button>
                        )}
                        {invoice.status === 'draft' && (
                            <Button onClick={issue}>
                                <Send className="mr-2 h-4 w-4" />
                                Issue &amp; send
                            </Button>
                        )}
                        {!settled && invoice.status !== 'draft' && (
                            <Button onClick={send}>
                                <Send className="mr-2 h-4 w-4" />
                                {delivery.sent_at ? 'Resend to guardian' : 'Send to guardian'}
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Invoice information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Status">
                                    <Badge variant={statusVariant(invoice.status)}>
                                        {invoice.status.replace('_', ' ')}
                                    </Badge>
                                </Field>
                                <Field label="Student">
                                    {invoice.student
                                        ? `${invoice.student.first_name} ${invoice.student.last_name}`
                                        : '—'}
                                </Field>
                                <Field label="Issue date">{invoice.issue_date ?? '—'}</Field>
                                <Field label="Due date">{invoice.due_date ?? '—'}</Field>
                                <Field label="Subtotal">{money(invoice.subtotal, invoice.currency)}</Field>
                                <Field label="Tax">
                                    {money(invoice.tax_amount, invoice.currency)}
                                    <span className="ml-2 text-xs text-muted-foreground">
                                        ({Math.round(Number(invoice.tax_rate) * 100)}%)
                                    </span>
                                </Field>
                                <Field label="Discount">
                                    {money(invoice.discount_amount, invoice.currency)}
                                </Field>
                                <Field label="Total">{money(invoice.total_amount, invoice.currency)}</Field>
                                <Field label="Paid">{money(invoice.amount_paid, invoice.currency)}</Field>
                                <Field label="Balance due">
                                    <span className="font-semibold">
                                        {money(invoice.balance_due, invoice.currency)}
                                    </span>
                                </Field>
                                {invoice.notes && (
                                    <div className="md:col-span-2">
                                        <Field label="Notes">{invoice.notes}</Field>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Line items</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {invoice.lines && invoice.lines.length > 0 ? (
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                            <th className="pb-2">Description</th>
                                            <th className="pb-2 text-right">Qty</th>
                                            <th className="pb-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {invoice.lines.map((line) => (
                                            <tr key={line.id} className="border-t border-border/60">
                                                <td className="py-2">{line.description}</td>
                                                <td className="py-2 text-right">{Number(line.quantity)}</td>
                                                <td className="py-2 text-right">
                                                    {money(line.amount, invoice.currency)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No line items recorded — the total was entered directly.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {invoice.payments && invoice.payments.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Payments</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                            <th className="pb-2">Date</th>
                                            <th className="pb-2">Method</th>
                                            <th className="pb-2">Status</th>
                                            <th className="pb-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {invoice.payments.map((payment) => (
                                            <tr key={payment.id} className="border-t border-border/60">
                                                <td className="py-2">{payment.payment_date}</td>
                                                <td className="py-2 capitalize">
                                                    {payment.payment_method.replace('_', ' ')}
                                                </td>
                                                <td className="py-2">
                                                    <Badge
                                                        variant={
                                                            payment.status === 'paid' ? 'success' : 'secondary'
                                                        }
                                                    >
                                                        {payment.status}
                                                    </Badge>
                                                </td>
                                                <td className="py-2 text-right">
                                                    {money(payment.amount, invoice.currency)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Guardian delivery</CardTitle>
                            <CardDescription>
                                {delivery.auto_send
                                    ? 'Invoices are sent automatically when issued.'
                                    : 'Automatic sending is off — use the send button.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Last sent</span>
                                    <span>
                                        {delivery.sent_at
                                            ? new Date(delivery.sent_at).toLocaleString()
                                            : 'Not sent yet'}
                                    </span>
                                </div>
                                {delivery.reminder_sent_at && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Reminder</span>
                                        <span>
                                            {new Date(delivery.reminder_sent_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                )}
                                <div className="flex flex-wrap gap-2">
                                    <ChannelPill
                                        icon={<Mail className="h-3.5 w-3.5" />}
                                        label="Email"
                                        active={Boolean(delivery.channels?.email)}
                                    />
                                    <ChannelPill
                                        icon={<MessageSquare className="h-3.5 w-3.5" />}
                                        label="SMS"
                                        active={Boolean(delivery.channels?.sms)}
                                    />
                                    <ChannelPill
                                        icon={<Bell className="h-3.5 w-3.5" />}
                                        label="In-app"
                                        active={Boolean(delivery.channels?.inapp)}
                                    />
                                </div>
                            </div>

                            <div className="border-t border-border/60 pt-4">
                                <p className="mb-2 text-xs uppercase tracking-wide text-muted-foreground">
                                    Recipients
                                </p>
                                {guardians.length === 0 ? (
                                    <p className="text-muted-foreground">
                                        No guardian is linked to this student yet, so nothing can be
                                        delivered. Link a guardian first.
                                    </p>
                                ) : (
                                    <ul className="space-y-2">
                                        {guardians.map((guardian) => (
                                            <li key={guardian.id} className="flex items-start justify-between gap-3">
                                                <div>
                                                    <div className="font-medium">{guardian.name}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {guardian.email ?? 'no email'}
                                                        {guardian.phone ? ` · ${guardian.phone}` : ''}
                                                    </div>
                                                </div>
                                                {guardian.is_financial && (
                                                    <Badge variant="info">financial</Badge>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Payment link</CardTitle>
                            <CardDescription>
                                {settled
                                    ? 'This invoice is settled, so the link is closed.'
                                    : 'A signed link valid for 60 days. No account needed.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {payUrl ? (
                                <>
                                    <code className="block max-h-24 overflow-auto break-all rounded-md bg-muted p-2 text-xs">
                                        {payUrl}
                                    </code>
                                    <div className="flex gap-2">
                                        <Button variant="outline" size="sm" onClick={copyLink} className="flex-1">
                                            {copied ? (
                                                <>
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Copied
                                                </>
                                            ) : (
                                                <>
                                                    <Copy className="mr-2 h-4 w-4" />
                                                    Copy link
                                                </>
                                            )}
                                        </Button>
                                        <Button variant="outline" size="sm" asChild>
                                            <a href={payUrl} target="_blank" rel="noreferrer">
                                                <ExternalLink className="mr-2 h-4 w-4" />
                                                Open
                                            </a>
                                        </Button>
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Nothing left to pay on this invoice.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppShell>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            <p className="text-base">{children}</p>
        </div>
    );
}

function ChannelPill({ icon, label, active }: { icon: ReactNode; label: string; active: boolean }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs ${
                active
                    ? 'border-success/40 bg-success/10 text-success'
                    : 'border-border/60 bg-muted/50 text-muted-foreground'
            }`}
        >
            {icon}
            {label}
        </span>
    );
}
