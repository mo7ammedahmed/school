import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ReportCardsShow({ reportCard }: { reportCard: { id: number; student: { first_name: string; last_name: string; email: string }; academic_year: { name: string }; grade: string; gpa: number; remarks: string; status: string } }) {
    return (
        <AppShell
            title="Report Card Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards', href: '/report-cards' },
                { label: `Report Card #${reportCard.id}` },
            ]}
        >
            <PageHeader
                title="Report Card Details"
                description={`${reportCard.student.first_name} ${reportCard.student.last_name}`}
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
                            <p className="text-base">{reportCard.student.first_name} {reportCard.student.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student Email</span>
                            <p className="text-base">{reportCard.student.email}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Academic Year</span>
                            <p className="text-base">{reportCard.academic_year.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Grade</span>
                            <p className="text-base">{reportCard.grade}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">GPA</span>
                            <p className="text-base">{Number(reportCard.gpa).toFixed(2)}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{reportCard.status}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Remarks</span>
                            <p className="text-base">{reportCard.remarks || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
