import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useBilingual } from '@/lib/i18n/bilingual';

type Bilingual = { id: number; name?: string | null; name_en?: string | null; name_ar?: string | null };

type FeeStructure = {
    id: number;
    fee_type_id: number;
    grade_level_id: number | null;
    amount: number;
    description: string | null;
    description_ar: string | null;
};

export default function FinanceFeeStructuresEdit({
    feeStructure,
    gradeLevels,
    feeTypes,
}: {
    feeStructure: FeeStructure;
    gradeLevels: Bilingual[];
    feeTypes: Bilingual[];
}) {
    const bilingual = useBilingual();

    return (
        <AppShell
            title="Edit Fee Structure"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Fee Structures', href: '/finance/fee-structures' },
                { label: 'Edit Fee Structure' },
            ]}
        >
            <PageHeader
                title="Edit Fee Structure"
                description={bilingual(feeStructure.description, feeStructure.description_ar, '')}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/fee-structures"><ArrowLeft className="me-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Fee Structure Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/finance/fee-structures/${feeStructure.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="fee_type_id">Fee Type</Label>
                                <select id="fee_type_id" name="fee_type_id" className="input" required defaultValue={feeStructure.fee_type_id}>
                                    <option value="">Select fee type</option>
                                    {feeTypes.map((feeType) => (
                                        <option key={feeType.id} value={feeType.id}>
                                            {bilingual(feeType.name, feeType.name_ar)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="grade_level_id">Grade Level</Label>
                                <select id="grade_level_id" name="grade_level_id" className="input" defaultValue={feeStructure.grade_level_id ?? ''}>
                                    <option value="">Every grade level</option>
                                    {gradeLevels.map((grade) => (
                                        <option key={grade.id} value={grade.id}>
                                            {bilingual(grade.name_en, grade.name_ar)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="amount">Amount</Label>
                                <Input id="amount" name="amount" type="number" step="0.01" defaultValue={feeStructure.amount} required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description (English)</Label>
                                <Input id="description" name="description" defaultValue={feeStructure.description ?? ''} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description_ar">Description (Arabic)</Label>
                                <Input id="description_ar" name="description_ar" dir="rtl" defaultValue={feeStructure.description_ar ?? ''} />
                            </div>
                            <div className="md:col-span-2">
                                <TranslatePair enId="description" arId="description_ar" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/fee-structures">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Fee Structure</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
