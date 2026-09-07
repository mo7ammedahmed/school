import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function QuizzesShow({ quiz }: {    quiz: { id: number; title: string; subject: { name: string }; section: { name: string }; total_marks: number; passing_marks: number; duration_minutes: number; status: string } }) {
    return (
        <AppShell
            title="Quiz Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Quizzes', href: '/quizzes' },
                { label: quiz.title },
            ]}
        >
            <PageHeader
                title="Quiz Details"
                description={quiz.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/quizzes"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/quizzes/${quiz.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Quiz Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Title</span>
                            <p className="text-base">{quiz.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject</span>
                            <p className="text-base">{quiz.subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{quiz.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Total Marks</span>
                            <p className="text-base">{quiz.total_marks}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Passing Marks</span>
                            <p className="text-base">{quiz.passing_marks}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Duration</span>
                            <p className="text-base">{quiz.duration_minutes} minutes</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{quiz.status}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
