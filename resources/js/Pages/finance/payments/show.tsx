import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinancePaymentsShow({ payment }: { payment: { id: number; payment_number: string; payment_date: string; amount: number; payment_method: string; status: string; reference_number: string; notes: string } }) {
    return (
        <AppShell
            title="Payment Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Payments', href: '/finance/payments' },
                { label: payment.payment_number },
            ]}
        >
            <PageHeader
                title="Payment Details"
                description={payment.payment_number}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/payments"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/finance/payments/${payment.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Payment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Payment Number</span>
                            <p className="text-base">{payment.payment_number}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{payment.status}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Payment Date</span>
                            <p className="text-base">{payment.payment_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Amount</span>
                            <p className="text-base font-semibold">{Number(payment.amount).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Payment Method</span>
                            <p className="text-base capitalize">{payment.payment_method.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Reference Number</span>
                            <p className="text-base">{payment.reference_number || '-'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Notes</span>
                            <p className="text-base">{payment.notes || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
