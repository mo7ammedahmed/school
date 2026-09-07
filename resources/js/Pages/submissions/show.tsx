import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SubmissionsShow({ submission }: { submission: { id: number; assignment: { title: string; description: string }; student: { first_name: string; last_name: string; email: string }; content: string; file_path: string; file_type: string; file_size: string; submitted_at: string; score: number; feedback: string } }) {
    return (
        <AppShell
            title="Submission Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assignments', href: '/assignments' },
                { label: 'Submissions', href: '/submissions' },
                { label: `Submission #${submission.id}` },
            ]}
        >
            <PageHeader
                title="Submission Details"
                description={`${submission.student.first_name} ${submission.student.last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/submissions"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/submissions/${submission.id}/edit`}>Grade</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Submission Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Assignment</span>
                            <p className="text-base">{submission.assignment.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student</span>
                            <p className="text-base">{submission.student.first_name} {submission.student.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Submitted At</span>
                            <p className="text-base">{submission.submitted_at}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">File</span>
                            <p className="text-base">{submission.file_type ? `${submission.file_type.toUpperCase()} (${submission.file_size})` : '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Score</span>
                            <p className="text-base font-semibold">{submission.score != null ? Number(submission.score).toFixed(2) : '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Feedback</span>
                            <p className="text-base">{submission.feedback || '-'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Content</span>
                            <p className="text-base whitespace-pre-wrap">{submission.content || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
