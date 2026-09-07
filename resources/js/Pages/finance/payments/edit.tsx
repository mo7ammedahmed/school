import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinancePaymentsEdit({ payment }: { payment: { id: number; payment_number: string; payment_date: string; amount: number; payment_method: string; status: string; reference_number: string; notes: string } }) {
    return (
        <AppShell
            title="Edit Payment"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Payments', href: '/finance/payments' },
                { label: 'Edit Payment' },
            ]}
        >
            <PageHeader
                title="Edit Payment"
                description={payment.payment_number}
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
                    <form className="space-y-6" method="POST" action={`/finance/payments/${payment.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="payment_number">Payment Number</Label>
                                <Input id="payment_number" name="payment_number" defaultValue={payment.payment_number} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={payment.status}>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="payment_date">Payment Date</Label>
                                <Input id="payment_date" name="payment_date" type="date" defaultValue={payment.payment_date} required />
                            </div>
                            <div>
                                <Label htmlFor="amount">Amount</Label>
                                <Input id="amount" name="amount" type="number" step="0.01" defaultValue={payment.amount} required />
                            </div>
                            <div>
                                <Label htmlFor="payment_method">Payment Method</Label>
                                <select id="payment_method" name="payment_method" className="input" required defaultValue={payment.payment_method}>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="moyasar">Moyasar</option>
                                    <option value="hyperpay">Hyperpay</option>
                                    <option value="stripe">Stripe</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="reference_number">Reference Number</Label>
                                <Input id="reference_number" name="reference_number" defaultValue={payment.reference_number} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <textarea id="notes" name="notes" className="input min-h-[100px]" defaultValue={payment.notes} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/payments">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Payment</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
