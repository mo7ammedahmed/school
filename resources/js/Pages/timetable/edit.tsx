import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function TimetableEdit({ schedule, sections, subjects, teachers }: { schedule: { id: number; section_id: number; subject_id: number; teacher_id: number; day_of_week: string; start_time: string; end_time: string; room: string }; sections: { id: number; name: string }[]; subjects: { id: number; name: string }[]; teachers: { id: number; first_name: string; last_name: string }[] }) {
    return (
        <AppShell
            title="Edit Schedule"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable', href: '/timetable' },
                { label: 'Edit Schedule' },
            ]}
        >
            <PageHeader
                title="Edit Schedule"
                description={`Schedule #${schedule.id}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/timetable"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Schedule Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/timetable/${schedule.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="section_id">Section</Label>
                                <select id="section_id" name="section_id" className="input" required defaultValue={schedule.section_id}>
                                    <option value="">Select section</option>
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id}>{section.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Subject</Label>
                                <select id="subject_id" name="subject_id" className="input" required defaultValue={schedule.subject_id}>
                                    <option value="">Select subject</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>{subject.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="teacher_id">Teacher</Label>
                                <select id="teacher_id" name="teacher_id" className="input" required defaultValue={schedule.teacher_id}>
                                    <option value="">Select teacher</option>
                                    {teachers.map((teacher) => (
                                        <option key={teacher.id} value={teacher.id}>{teacher.first_name} {teacher.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="day_of_week">Day</Label>
                                <select id="day_of_week" name="day_of_week" className="input" required defaultValue={schedule.day_of_week}>
                                    <option value="monday">Monday</option>
                                    <option value="tuesday">Tuesday</option>
                                    <option value="wednesday">Wednesday</option>
                                    <option value="thursday">Thursday</option>
                                    <option value="friday">Friday</option>
                                    <option value="saturday">Saturday</option>
                                    <option value="sunday">Sunday</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="start_time">Start Time</Label>
                                <Input id="start_time" name="start_time" type="time" defaultValue={schedule.start_time} required />
                            </div>
                            <div>
                                <Label htmlFor="end_time">End Time</Label>
                                <Input id="end_time" name="end_time" type="time" defaultValue={schedule.end_time} required />
                            </div>
                            <div>
                                <Label htmlFor="room">Room</Label>
                                <Input id="room" name="room" defaultValue={schedule.room} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/timetable">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Schedule</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
