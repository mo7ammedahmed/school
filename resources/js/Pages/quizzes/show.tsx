import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { QuestionPaper, type ExamQuestion } from '@/components/ui/question-paper';
import { ArrowLeft, ListChecks } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function QuizzesShow({ quiz, canManage = true }: { quiz: { id: number; title: string; subject: { name: string } | null; section: { name: string } | null; total_marks: number; passing_marks: number; duration_minutes: number; status: string; questions?: ExamQuestion[] | null }; canManage?: boolean }) {
    const questions = quiz.questions ?? [];

    return (
        <AppShell
            title="Quiz Details"
            breadcrumbs={[
                { label: 'Dashboard', href: canManage ? '/dashboard' : '/student/dashboard' },
                { label: canManage ? 'Quizzes' : 'Assignments', href: canManage ? '/quizzes' : '/student/assignments' },
                { label: quiz.title },
            ]}
        >
            <PageHeader
                title="Quiz Details"
                description={quiz.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={canManage ? '/quizzes' : '/student/assignments'}><ArrowLeft className="me-2 h-4 w-4" />Back</Link>
                        </Button>
                        {canManage && <Button asChild>
                            <Link href={`/quizzes/${quiz.id}/edit`}>Edit</Link>
                        </Button>}
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
                            <p className="text-base">{quiz.subject?.name ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{quiz.section?.name ?? '—'}</p>
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

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ListChecks className="h-4 w-4 text-muted-foreground" />
                        Questions
                    </CardTitle>
                    <CardDescription>
                        {questions.length > 0
                            ? `${questions.length} question${questions.length === 1 ? '' : 's'} imported from Word.`
                            : 'This quiz has no questions attached.'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <QuestionPaper questions={questions} showAnswers={canManage} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
