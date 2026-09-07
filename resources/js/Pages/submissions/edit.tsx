import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SubmissionsEdit({ submission, assignments, students }: { submission: { id: number; assignment_id: number; student_id: number; content: string; score: number; feedback: string }; assignments: { id: number; title: string }[]; students: { id: number; first_name: string; last_name: string }[] }) {
    return (
        <AppShell
            title="Grade Submission"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assignments', href: '/assignments' },
                { label: 'Submissions', href: '/submissions' },
                { label: 'Grade Submission' },
            ]}
        >
            <PageHeader
                title="Grade Submission"
                description={`Submission #${submission.id}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/submissions"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Submission Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/submissions/${submission.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="assignment_id">Assignment</Label>
                                <select id="assignment_id" name="assignment_id" className="input" required defaultValue={submission.assignment_id}>
                                    <option value="">Select assignment</option>
                                    {assignments.map((assignment) => (
                                        <option key={assignment.id} value={assignment.id}>{assignment.title}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required defaultValue={submission.student_id}>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.first_name} {student.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="content">Content</Label>
                                <textarea id="content" name="content" className="input min-h-[100px]" defaultValue={submission.content} />
                            </div>
                            <div>
                                <Label htmlFor="score">Score</Label>
                                <Input id="score" name="score" type="number" step="0.01" defaultValue={submission.score} />
                            </div>
                            <div>
                                <Label htmlFor="feedback">Feedback</Label>
                                <Input id="feedback" name="feedback" defaultValue={submission.feedback} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/submissions">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Submission</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
