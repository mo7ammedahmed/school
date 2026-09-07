import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ReportCardsCreate({ students, academicYears }: { students: { id: number; first_name: string; last_name: string }[]; academicYears: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="New Report Card"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards', href: '/report-cards' },
                { label: 'New Report Card' },
            ]}
        >
            <PageHeader
                title="New Report Card"
                description="Create a new report card"
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
                    <form className="space-y-6" method="POST" action="/report-cards">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.first_name} {student.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="academic_year_id">Academic Year</Label>
                                <select id="academic_year_id" name="academic_year_id" className="input" required>
                                    <option value="">Select academic year</option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>{year.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="grade">Grade</Label>
                                <Input id="grade" name="grade" required />
                            </div>
                            <div>
                                <Label htmlFor="gpa">GPA</Label>
                                <Input id="gpa" name="gpa" type="number" step="0.01" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="draft">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="remarks">Remarks</Label>
                                <Input id="remarks" name="remarks" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/report-cards">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Report Card</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
