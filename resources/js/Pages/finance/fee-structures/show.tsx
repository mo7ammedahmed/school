import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useBilingual } from '@/lib/i18n/bilingual';

type FeeStructure = {
    id: number;
    amount: number;
    description: string | null;
    description_ar: string | null;
    fee_type: { name: string | null; name_ar: string | null } | null;
    grade_level: { name_en: string | null; name_ar: string | null } | null;
};

export default function FinanceFeeStructuresShow({ feeStructure }: { feeStructure: FeeStructure }) {
    const bilingual = useBilingual();
    const heading = bilingual(feeStructure.description, feeStructure.description_ar, '');

    return (
        <AppShell
            title="Fee Structure Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Fee Structures', href: '/finance/fee-structures' },
                { label: heading },
            ]}
        >
            <PageHeader
                title="Fee Structure Details"
                description={heading}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/finance/fee-structures"><ArrowLeft className="me-2 h-4 w-4" />Back</Link>
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
                            <span className="text-sm font-medium text-muted-foreground">Description (English)</span>
                            <p className="text-base" dir="ltr">{feeStructure.description ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Description (Arabic)</span>
                            <p className="text-base" dir="rtl">{feeStructure.description_ar ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Fee Type</span>
                            <p className="text-base">
                                {bilingual(feeStructure.fee_type?.name, feeStructure.fee_type?.name_ar)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Grade Level</span>
                            <p className="text-base">
                                {bilingual(feeStructure.grade_level?.name_en, feeStructure.grade_level?.name_ar, 'Every grade')}
                            </p>
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
