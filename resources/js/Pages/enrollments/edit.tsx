import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function EnrollmentsEdit({ enrollment, students, sections }: { enrollment: { id: number; student_id: number; section_id: number; enrollment_date: string; status: string }; students: { id: number; first_name: string; last_name: string }[]; sections: { id: number; name: string; grade: { name: string } }[] }) {
    return (
        <AppShell
            title="Edit Enrollment"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Enrollments', href: '/enrollments' },
                { label: 'Edit Enrollment' },
            ]}
        >
            <PageHeader
                title="Edit Enrollment"
                description="Update enrollment information"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/enrollments"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Enrollment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/enrollments/${enrollment.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id} selected={student.id === enrollment.student_id}>{student.first_name} {student.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="section_id">Section</Label>
                                <select id="section_id" name="section_id" className="input" required>
                                    <option value="">Select section</option>
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id} selected={section.id === enrollment.section_id}>{section.name} - {section.grade.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="enrollment_date">Enrollment Date</Label>
                                <Input id="enrollment_date" name="enrollment_date" type="date" defaultValue={enrollment.enrollment_date} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" defaultValue={enrollment.status}>
                                    <option value="enrolled">Enrolled</option>
                                    <option value="withdrawn">Withdrawn</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/enrollments">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Enrollment</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
