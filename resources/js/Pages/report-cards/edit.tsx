import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ReportCardsEdit({ reportCard, students, academicYears }: { reportCard: { id: number; student_id: number; academic_year_id: number; grade: string; gpa: number; remarks: string; status: string }; students: { id: number; first_name: string; last_name: string }[]; academicYears: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="Edit Report Card"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards', href: '/report-cards' },
                { label: 'Edit Report Card' },
            ]}
        >
            <PageHeader
                title="Edit Report Card"
                description={`Report Card #${reportCard.id}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/report-cards"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Report Card Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/report-cards/${reportCard.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required defaultValue={reportCard.student_id}>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.first_name} {student.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="academic_year_id">Academic Year</Label>
                                <select id="academic_year_id" name="academic_year_id" className="input" required defaultValue={reportCard.academic_year_id}>
                                    <option value="">Select academic year</option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>{year.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="grade">Grade</Label>
                                <Input id="grade" name="grade" defaultValue={reportCard.grade} required />
                            </div>
                            <div>
                                <Label htmlFor="gpa">GPA</Label>
                                <Input id="gpa" name="gpa" type="number" step="0.01" defaultValue={reportCard.gpa} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={reportCard.status}>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="remarks">Remarks</Label>
                                <Input id="remarks" name="remarks" defaultValue={reportCard.remarks} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/report-cards">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Report Card</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
