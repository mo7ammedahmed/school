import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinancePaymentsCreate({ invoices }: { invoices: { id: number; invoice_number: string }[] }) {
    return (
        <AppShell
            title="Create Payment"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Payments', href: '/finance/payments' },
                { label: 'Create Payment' },
            ]}
        >
            <PageHeader
                title="Create Payment"
                description="Add a new payment"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/payments"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Payment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/finance/payments">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="invoice_id">Invoice</Label>
                                <select id="invoice_id" name="invoice_id" className="input" required>
                                    <option value="">Select invoice</option>
                                    {invoices.map((invoice) => (
                                        <option key={invoice.id} value={invoice.id}>{invoice.invoice_number}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="payment_number">Payment Number</Label>
                                <Input id="payment_number" name="payment_number" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="completed">
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="payment_date">Payment Date</Label>
                                <Input id="payment_date" name="payment_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="amount">Amount</Label>
                                <Input id="amount" name="amount" type="number" step="0.01" required />
                            </div>
                            <div>
                                <Label htmlFor="payment_method">Payment Method</Label>
                                <select id="payment_method" name="payment_method" className="input" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="moyasar">Moyasar</option>
                                    <option value="hyperpay">Hyperpay</option>
                                    <option value="stripe">Stripe</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="reference_number">Reference Number</Label>
                                <Input id="reference_number" name="reference_number" />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <textarea id="notes" name="notes" className="input min-h-[100px]" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/payments">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Payment</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
