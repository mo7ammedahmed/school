import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function TimetableShow({ schedule }: { schedule: { id: number; section: { name: string }; subject: { name: string }; teacher: { first_name: string; last_name: string }; day_of_week: string; start_time: string; end_time: string;    room: { name: string } | null } }) {
    return (
        <AppShell
            title="Schedule Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable', href: '/timetable' },
                { label: `Schedule #${schedule.id}` },
            ]}
        >
            <PageHeader
                title="Schedule Details"
                description={`${schedule.section.name} - ${schedule.subject.name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/timetable"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/timetable/${schedule.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Schedule Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{schedule.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject</span>
                            <p className="text-base">{schedule.subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Teacher</span>
                            <p className="text-base">{schedule.teacher.first_name} {schedule.teacher.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Day</span>
                            <p className="text-base capitalize">{schedule.day_of_week}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Time</span>
                            <p className="text-base">{schedule.start_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Time</span>
                            <p className="text-base">{schedule.end_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Room</span>
                            <p className="text-base">{schedule.room?.name || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
