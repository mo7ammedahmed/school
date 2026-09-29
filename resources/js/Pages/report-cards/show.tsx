import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useBilingual } from '@/lib/i18n/bilingual';

type ReportCard = {
    id: number;
    student: { first_name: string | null; last_name: string | null; email: string | null };
    academic_year: { name_en: string | null; name_ar: string | null };
    gpa: number;
    comments: string | null;
    comments_ar: string | null;
    published_at: string | null;
};

export default function ReportCardsShow({ reportCard }: { reportCard: ReportCard }) {
    const bilingual = useBilingual();
    const student = `${reportCard.student.first_name ?? ''} ${reportCard.student.last_name ?? ''}`.trim();

    return (
        <AppShell
            title="Report Card Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards', href: '/report-cards' },
                { label: student || `Report Card #${reportCard.id}` },
            ]}
        >
            <PageHeader
                title="Report Card Details"
                description={student}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/report-cards"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/report-cards/${reportCard.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Report Card Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student</span>
                            <p className="text-base">{student || '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student Email</span>
                            <p className="text-base">{reportCard.student.email ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Academic Year</span>
                            <p className="text-base">
                                {bilingual(reportCard.academic_year.name_en, reportCard.academic_year.name_ar)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">GPA</span>
                            <p className="text-base">{Number(reportCard.gpa).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">
                                {reportCard.published_at
                                    ? `Published on ${reportCard.published_at.slice(0, 10)}`
                                    : 'Draft — not visible to the guardian'}
                            </p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Comments (English)</span>
                            <p className="text-base whitespace-pre-wrap" dir="ltr">{reportCard.comments ?? '—'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Comments (Arabic)</span>
                            <p className="text-base whitespace-pre-wrap" dir="rtl">{reportCard.comments_ar ?? '—'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
