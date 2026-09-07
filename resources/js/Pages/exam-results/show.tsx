import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ExamResultsShow({ result }: { result: { id: number; exam: { name: string; total_marks: number }; student: { first_name: string; last_name: string; email: string }; score: number; notes: string } }) {
    return (
        <AppShell
            title="Result Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams', href: '/exams' },
                { label: 'Results', href: '/exam-results' },
                { label: `Result #${result.id}` },
            ]}
        >
            <PageHeader
                title="Result Details"
                description={`${result.student.first_name} ${result.student.last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/exam-results"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/exam-results/${result.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Result Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Exam</span>
                            <p className="text-base">{result.exam.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student</span>
                            <p className="text-base">{result.student.first_name} {result.student.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Score</span>
                            <p className="text-base font-semibold">{Number(result.score).toFixed(2)} / {result.exam.total_marks}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Notes</span>
                            <p className="text-base">{result.notes || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
