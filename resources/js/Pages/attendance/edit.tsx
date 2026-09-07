import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AttendanceEdit({
    attendance,
    students,
    sessions,
}: {
    attendance: { id: number; attendance_session_id: number; student_id: number; status: string; notes: string };
    students: { id: number; first_name: string; last_name: string }[];
    sessions: { id: number; label: string }[];
}) {
    return (
        <AppShell
            title="Edit Attendance"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Attendance', href: '/attendance' },
                { label: 'Edit Attendance' },
            ]}
        >
            <PageHeader
                title="Edit Attendance"
                description={`Record #${attendance.id}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/attendance"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Attendance Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/attendance/${attendance.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <Label htmlFor="attendance_session_id">Attendance Session</Label>
                                <select id="attendance_session_id" name="attendance_session_id" className="input" required defaultValue={attendance.attendance_session_id}>
                                    <option value="">Select session</option>
                                    {sessions.map((session) => (
                                        <option key={session.id} value={session.id}>{session.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required defaultValue={attendance.student_id}>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.first_name} {student.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={attendance.status}>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Input id="notes" name="notes" defaultValue={attendance.notes} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/attendance">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Attendance</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
