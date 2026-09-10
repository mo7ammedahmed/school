import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceDiscountsEdit({ discount }: { discount: { id: number; name: string; type: string; value: number; start_date: string; end_date: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Edit Discount"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Discounts', href: '/finance/discounts' },
                { label: 'Edit Discount' },
            ]}
        >
            <PageHeader
                title="Edit Discount"
                description={discount.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/discounts"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Discount Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/finance/discounts/${discount.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" name="name" defaultValue={discount.name} required />
                            </div>
                            <div>
                                <Label htmlFor="type">Type</Label>
                                <select id="type" name="type" className="input" required defaultValue={discount.type}>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="value">Value</Label>
                                <Input id="value" name="value" type="number" step="0.01" defaultValue={discount.value} required />
                            </div>
                            <div>
                                <Label htmlFor="is_active">Active</Label>
                                <select id="is_active" name="is_active" className="input" required defaultValue={String(discount.is_active)}>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input id="start_date" name="start_date" type="date" defaultValue={discount.start_date} required />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input id="end_date" name="end_date" type="date" defaultValue={discount.end_date} required />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/discounts">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Discount</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
