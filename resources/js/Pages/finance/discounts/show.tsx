import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceDiscountsShow({ discount }: { discount: { id: number; name: string; type: string; value: number; start_date: string; end_date: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Discount Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Discounts', href: '/finance/discounts' },
                { label: discount.name },
            ]}
        >
            <PageHeader
                title="Discount Details"
                description={discount.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/discounts"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/finance/discounts/${discount.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Discount Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Name</span>
                            <p className="text-base">{discount.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Type</span>
                            <p className="text-base capitalize">{discount.type}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Value</span>
                            <p className="text-base">{discount.type === 'percentage' ? `${discount.value}%` : Number(discount.value).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">{discount.is_active ? 'Active' : 'Inactive'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Date</span>
                            <p className="text-base">{discount.start_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Date</span>
                            <p className="text-base">{discount.end_date}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
