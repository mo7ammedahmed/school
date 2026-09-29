import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceRefundsEdit({ refund }: { refund: { id: number; amount: number; reason: string | null; reason_ar: string | null; status: string } }) {
    return (
        <AppShell
            title="Edit Refund"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Refunds', href: '/finance/refunds' },
                { label: 'Edit Refund' },
            ]}
        >
            <PageHeader
                title="Edit Refund"
                description="Update refund information"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/refunds"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Refund Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/finance/refunds/${refund.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="amount">Amount</Label>
                                <Input id="amount" name="amount" type="number" step="0.01" defaultValue={refund.amount} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={refund.status}>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="reason">Reason (English)</Label>
                                <textarea id="reason" name="reason" className="input min-h-[100px]" defaultValue={refund.reason ?? ''} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="reason_ar">Reason (Arabic)</Label>
                                <textarea id="reason_ar" name="reason_ar" dir="rtl" className="input min-h-[100px]" defaultValue={refund.reason_ar ?? ''} />
                            </div>
                            <div className="md:col-span-2">
                                <TranslatePair enId="reason" arId="reason_ar" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/refunds">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Refund</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
