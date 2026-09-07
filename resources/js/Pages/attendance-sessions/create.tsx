import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AttendanceSessionsCreate({ sections, teachers, subjects, semesters, academicYears }: { sections: { id: number; name: string }[]; teachers: { id: number; first_name: string; last_name: string }[]; subjects: { id: number; name: string }[]; semesters: { id: number; name: string }[]; academicYears: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="New Attendance Session"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Attendance', href: '/attendance' },
                { label: 'Sessions', href: '/attendance-sessions' },
                { label: 'New Session' },
            ]}
        >
            <PageHeader
                title="New Attendance Session"
                description="Create a new attendance session"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/attendance-sessions"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Session Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/attendance-sessions">
                        <div className="grid gap-6 md:grid-cols-2">
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
                                <Label htmlFor="subject_id">Subject</Label>
                                <select id="subject_id" name="subject_id" className="input" required>
                                    <option value="">Select subject</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>{subject.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="teacher_id">Teacher</Label>
                                <select id="teacher_id" name="teacher_id" className="input" required>
                                    <option value="">Select teacher</option>
                                    {teachers.map((teacher) => (
                                        <option key={teacher.id} value={teacher.id}>{teacher.first_name} {teacher.last_name}</option>
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
                                <Label htmlFor="semester_id">Semester</Label>
                                <select id="semester_id" name="semester_id" className="input" required>
                                    <option value="">Select semester</option>
                                    {semesters.map((semester) => (
                                        <option key={semester.id} value={semester.id}>{semester.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="session_date">Session Date</Label>
                                <Input id="session_date" name="session_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="start_time">Start Time</Label>
                                <Input id="start_time" name="start_time" type="time" required />
                            </div>
                            <div>
                                <Label htmlFor="end_time">End Time</Label>
                                <Input id="end_time" name="end_time" type="time" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="scheduled">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="is_finalized">Finalized</Label>
                                <select id="is_finalized" name="is_finalized" className="input" required defaultValue="0">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/attendance-sessions">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Session</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
