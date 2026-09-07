import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceRefundsShow({ refund }: { refund: { id: number; amount: number; reason: string; status: string } }) {
    return (
        <AppShell
            title="Refund Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Refunds', href: '/finance/refunds' },
                { label: `Refund #${refund.id}` },
            ]}
        >
            <PageHeader
                title="Refund Details"
                description={`Refund #${refund.id}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/refunds"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/finance/refunds/${refund.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Refund Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Amount</span>
                            <p className="text-base font-semibold">{Number(refund.amount).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{refund.status}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Reason</span>
                            <p className="text-base">{refund.reason}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
