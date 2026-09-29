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

export default function FinanceFeeStructuresCreate({
    gradeLevels,
    feeTypes,
}: {
    gradeLevels: Bilingual[];
    feeTypes: Bilingual[];
}) {
    const bilingual = useBilingual();

    return (
        <AppShell
            title="Create Fee Structure"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Fee Structures', href: '/finance/fee-structures' },
                { label: 'Create Fee Structure' },
            ]}
        >
            <PageHeader
                title="Create Fee Structure"
                description="Set the amount a grade level pays for one kind of charge"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/fee-structures"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Fee Structure Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/finance/fee-structures">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="fee_type_id">Fee Type</Label>
                                <select id="fee_type_id" name="fee_type_id" className="input" required defaultValue="">
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
                                <select id="grade_level_id" name="grade_level_id" className="input" defaultValue="">
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
                                <Input id="amount" name="amount" type="number" step="0.01" required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description (English)</Label>
                                <Input id="description" name="description" />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description_ar">Description (Arabic)</Label>
                                <Input id="description_ar" name="description_ar" dir="rtl" />
                            </div>
                            <div className="md:col-span-2">
                                <TranslatePair enId="description" arId="description_ar" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/fee-structures">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Fee Structure</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
