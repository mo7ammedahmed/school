import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ExamsEdit({ exam, subjects, sections }: { exam: { id: number; name: string; subject_id: number; section_id: number; exam_date: string; start_time: string; end_time: string; total_marks: number; passing_marks: number; status: string }; subjects: { id: number; name: string }[]; sections: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="Edit Exam"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams', href: '/exams' },
                { label: 'Edit Exam' },
            ]}
        >
            <PageHeader
                title="Edit Exam"
                description={exam.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/exams"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Exam Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/exams/${exam.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Exam Name</Label>
                                <Input id="name" name="name" defaultValue={exam.name} required />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Subject</Label>
                                <select id="subject_id" name="subject_id" className="input" required defaultValue={exam.subject_id}>
                                    <option value="">Select subject</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>{subject.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="section_id">Section</Label>
                                <select id="section_id" name="section_id" className="input" required defaultValue={exam.section_id}>
                                    <option value="">Select section</option>
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id}>{section.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="exam_date">Exam Date</Label>
                                <Input id="exam_date" name="exam_date" type="date" defaultValue={exam.exam_date} required />
                            </div>
                            <div>
                                <Label htmlFor="start_time">Start Time</Label>
                                <Input id="start_time" name="start_time" type="time" defaultValue={exam.start_time} required />
                            </div>
                            <div>
                                <Label htmlFor="end_time">End Time</Label>
                                <Input id="end_time" name="end_time" type="time" defaultValue={exam.end_time} required />
                            </div>
                            <div>
                                <Label htmlFor="total_marks">Total Marks</Label>
                                <Input id="total_marks" name="total_marks" type="number" defaultValue={exam.total_marks} required />
                            </div>
                            <div>
                                <Label htmlFor="passing_marks">Passing Marks</Label>
                                <Input id="passing_marks" name="passing_marks" type="number" defaultValue={exam.passing_marks} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={exam.status}>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/exams">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Exam</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
