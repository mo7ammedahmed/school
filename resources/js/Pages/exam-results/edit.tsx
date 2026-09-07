import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ExamResultsEdit({ result, exams, students }: { result: { id: number; exam_id: number; student_id: number; score: number; notes: string }; exams: { id: number; name: string }[]; students: { id: number; first_name: string; last_name: string }[] }) {
    return (
        <AppShell
            title="Edit Exam Result"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams', href: '/exams' },
                { label: 'Results', href: '/exam-results' },
                { label: 'Edit Result' },
            ]}
        >
            <PageHeader
                title="Edit Exam Result"
                description={`Result #${result.id}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/exam-results"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Result Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/exam-results/${result.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="exam_id">Exam</Label>
                                <select id="exam_id" name="exam_id" className="input" required defaultValue={result.exam_id}>
                                    <option value="">Select exam</option>
                                    {exams.map((exam) => (
                                        <option key={exam.id} value={exam.id}>{exam.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required defaultValue={result.student_id}>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.first_name} {student.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="score">Score</Label>
                                <Input id="score" name="score" type="number" step="0.01" defaultValue={result.score} required />
                            </div>
                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <Input id="notes" name="notes" defaultValue={result.notes} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/exam-results">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Result</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
