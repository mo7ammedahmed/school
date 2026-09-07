import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AttendanceSessionsShow({ session }: { session: { id: number; section: { name: string }; subject: { name: string }; teacher: { first_name: string; last_name: string }; session_date: string; start_time: string; end_time: string; status: string; is_finalized: boolean; records: { id: number; student: { first_name: string; last_name: string }; status: string; notes: string }[] } }) {
    return (
        <AppShell
            title="Session Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Attendance', href: '/attendance' },
                { label: 'Sessions', href: '/attendance-sessions' },
                { label: `Session #${session.id}` },
            ]}
        >
            <PageHeader
                title="Session Details"
                description={`${session.section.name} - ${session.subject.name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/attendance-sessions"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/attendance-sessions/${session.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Session Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{session.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject</span>
                            <p className="text-base">{session.subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Teacher</span>
                            <p className="text-base">{session.teacher.first_name} {session.teacher.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Session Date</span>
                            <p className="text-base">{session.session_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Time</span>
                            <p className="text-base">{session.start_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Time</span>
                            <p className="text-base">{session.end_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{session.status}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Finalized</span>
                            <p className="text-base">{session.is_finalized ? 'Yes' : 'No'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Attendance Records ({session.records.length})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-left font-medium">Student</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                    <th className="px-4 py-3 text-left font-medium">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                {session.records.map((record) => (
                                    <tr key={record.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">{record.student.first_name} {record.student.last_name}</td>
                                        <td className="px-4 py-3 capitalize">{record.status}</td>
                                        <td className="px-4 py-3">{record.notes || '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
