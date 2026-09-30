import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

export default function FinanceInvoicesCreate({ students }: { students: { id: number; name: string }[] }) {
    const { locale } = useLocale();

    return (
        <AppShell
            title={t(locale, 'finance.invoices.create.title')}
            breadcrumbs={[
                { label: t(locale, 'nav.dashboard'), href: '/dashboard' },
                { label: t(locale, 'nav.finance'), href: '/finance/invoices' },
                { label: t(locale, 'finance.invoices.title'), href: '/finance/invoices' },
                { label: t(locale, 'finance.invoices.create.title') },
            ]}
        >
            <PageHeader
                title={t(locale, 'finance.invoices.create.title')}
                description={t(locale, 'finance.invoices.create.description')}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/invoices">
                            <ArrowLeft className="me-2 size-4 rtl:-scale-x-100" aria-hidden="true" />
                            {t(locale, 'finance.invoices.back')}
                        </Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>{t(locale, 'finance.invoices.information')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/finance/invoices">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="min-w-0">
                                <Label htmlFor="student_id">{t(locale, 'finance.invoices.student')}</Label>
                                <Select id="student_id" name="student_id" required defaultValue="">
                                    <option value="">{t(locale, 'finance.invoices.selectStudent')}</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>
                                            {student.name}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div className="min-w-0">
                                <Label htmlFor="invoice_number">{t(locale, 'finance.invoices.number')}</Label>
                                <Input id="invoice_number" name="invoice_number" required />
                            </div>
                            <div className="min-w-0">
                                <Label htmlFor="issue_date">{t(locale, 'finance.invoices.issueDate')}</Label>
                                <Input id="issue_date" name="issue_date" type="date" />
                            </div>
                            <div className="min-w-0">
                                <Label htmlFor="due_date">{t(locale, 'finance.invoices.dueDate')}</Label>
                                <Input id="due_date" name="due_date" type="date" required />
                            </div>
                            <div className="min-w-0">
                                <Label htmlFor="subtotal">{t(locale, 'finance.invoices.subtotal')}</Label>
                                <Input id="subtotal" name="subtotal" type="number" step="0.01" required />
                            </div>
                            <div className="min-w-0">
                                <Label htmlFor="tax_rate">{t(locale, 'finance.invoices.taxRate')}</Label>
                                <Input id="tax_rate" name="tax_rate" type="number" step="0.01" defaultValue="0" />
                            </div>
                            <div className="min-w-0">
                                <Label htmlFor="discount_amount">{t(locale, 'finance.invoices.discountAmount')}</Label>
                                <Input id="discount_amount" name="discount_amount" type="number" step="0.01" defaultValue="0" />
                            </div>
                            <div className="min-w-0 md:col-span-2">
                                <Label htmlFor="notes">{t(locale, 'finance.invoices.notes')}</Label>
                                <Textarea id="notes" name="notes" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/invoices">{t(locale, 'finance.invoices.cancel')}</Link>
                            </Button>
                            <Button type="submit">{t(locale, 'finance.invoices.create.submit')}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
