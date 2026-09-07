import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function QuizzesCreate({ subjects, sections }: { subjects: { id: number; name: string }[]; sections: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="New Quiz"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Quizzes', href: '/quizzes' },
                { label: 'New Quiz' },
            ]}
        >
            <PageHeader
                title="New Quiz"
                description="Create a new quiz"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/quizzes"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Quiz Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/quizzes">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" required />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Subject</Label>
                                <select id="subject_id" name="subject_id" className="input" required>
                                    <option value="">Select subject</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>{subject.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="section_id">Section</Label>
                                <select id="section_id" name="section_id" className="input" required>
                                    <option value="">Select section</option>
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id}>{section.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="due_date">Due Date</Label>
                                <Input id="due_date" name="due_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="total_marks">Total Marks</Label>
                                <Input id="total_marks" name="total_marks" type="number" required />
                            </div>
                            <div>
                                <Label htmlFor="passing_marks">Passing Marks</Label>
                                <Input id="passing_marks" name="passing_marks" type="number" required />
                            </div>
                            <div>
                                <Label htmlFor="duration_minutes">Duration (Minutes)</Label>
                                <Input id="duration_minutes" name="duration_minutes" type="number" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="draft">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/quizzes">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Quiz</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
