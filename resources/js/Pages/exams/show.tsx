import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ExamsShow({ exam }: { exam: { id: number; name: string; subject: { name: string }; section: { name: string }; exam_date: string; start_time: string; end_time: string; total_marks: number; passing_marks: number; status: string } }) {
    return (
        <AppShell
            title="Exam Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams', href: '/exams' },
                { label: exam.name },
            ]}
        >
            <PageHeader
                title="Exam Details"
                description={exam.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/exams"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/exams/${exam.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Exam Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Exam Name</span>
                            <p className="text-base">{exam.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject</span>
                            <p className="text-base">{exam.subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{exam.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Exam Date</span>
                            <p className="text-base">{exam.exam_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Time</span>
                            <p className="text-base">{exam.start_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Time</span>
                            <p className="text-base">{exam.end_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Total Marks</span>
                            <p className="text-base">{exam.total_marks}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Passing Marks</span>
                            <p className="text-base">{exam.passing_marks}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{exam.status}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
