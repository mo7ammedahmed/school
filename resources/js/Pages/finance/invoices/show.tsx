import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceInvoicesShow({ invoice }: { invoice: { id: number; invoice_number: string; issue_date: string; due_date: string; subtotal: number; tax_rate: number; discount_amount: number; total: number; status: string; notes: string } }) {
    return (
        <AppShell
            title="Invoice Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Invoices', href: '/finance/invoices' },
                { label: invoice.invoice_number },
            ]}
        >
            <PageHeader
                title="Invoice Details"
                description={invoice.invoice_number}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/invoices"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/finance/invoices/${invoice.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Invoice Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Invoice Number</span>
                            <p className="text-base">{invoice.invoice_number}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{invoice.status}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Issue Date</span>
                            <p className="text-base">{invoice.issue_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Due Date</span>
                            <p className="text-base">{invoice.due_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subtotal</span>
                            <p className="text-base">{Number(invoice.subtotal).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Tax Rate</span>
                            <p className="text-base">{invoice.tax_rate}%</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Discount</span>
                            <p className="text-base">{Number(invoice.discount_amount).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Total</span>
                            <p className="text-base font-semibold">{Number(invoice.total).toFixed(2)}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Notes</span>
                            <p className="text-base">{invoice.notes || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
