import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinancePaymentReturn({ payment }: { payment: { id: number; invoice_number: string; amount: number; payment_method: string; reference_number: string; status: string; payment_date: string } }) {
    return (
        <AppShell
            title="Process Payment"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/payments' },
                { label: 'Payments', href: '/finance/payments' },
                { label: 'Offline Payments', href: '/finance/payments/offline' },
                { label: 'Process Payment' },
            ]}
        >
            <PageHeader
                title="Process Payment"
                description={`Payment for ${payment.invoice_number}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/payments/offline"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Payment Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Invoice Number</span>
                            <p className="text-base">{payment.invoice_number}</p>
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
                            <p className="text-base">{payment.reference_number}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Payment Date</span>
                            <p className="text-base">{payment.payment_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Current Status</span>
                            <p className="text-base capitalize">{payment.status}</p>
                        </div>
                    </div>

                    <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p className="text-sm text-yellow-800">
                            <strong>Note:</strong> This is a bank transfer/offline payment. Please verify the payment
                            in your bank account before marking it as completed.
                        </p>
                    </div>

                    <div className="mt-6 flex gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/finance/payments/offline">Cancel</Link>
                        </Button>
                        <Button>Mark as Completed</Button>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
