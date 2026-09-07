import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceFeeStructuresShow({ feeStructure }: { feeStructure: { id: number; name: string; fee_type: string; grade_level: string; amount: number } }) {
    return (
        <AppShell
            title="Fee Structure Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Fee Structures', href: '/finance/fee-structures' },
                { label: feeStructure.name },
            ]}
        >
            <PageHeader
                title="Fee Structure Details"
                description={feeStructure.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/fee-structures"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/finance/fee-structures/${feeStructure.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Fee Structure Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Name</span>
                            <p className="text-base">{feeStructure.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Fee Type</span>
                            <p className="text-base">{feeStructure.fee_type}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Grade Level</span>
                            <p className="text-base">{feeStructure.grade_level}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Amount</span>
                            <p className="text-base font-semibold">{Number(feeStructure.amount).toFixed(2)}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
