import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function EventsEdit({ event }: { event: { id: number; title: string; event_date: string; start_time: string; end_time: string; location: string; target_audience: string; description: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Edit Event"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Events', href: '/events' },
                { label: 'Edit Event' },
            ]}
        >
            <PageHeader
                title="Edit Event"
                description={event.title}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/content/events"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Event Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/content/events/${event.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Event Title</Label>
                                <Input id="title" name="title" defaultValue={event.title} required />
                            </div>
                            <div>
                                <Label htmlFor="event_date">Event Date</Label>
                                <Input id="event_date" name="event_date" type="date" defaultValue={event.event_date} required />
                            </div>
                            <div>
                                <Label htmlFor="start_time">Start Time</Label>
                                <Input id="start_time" name="start_time" type="time" defaultValue={event.start_time} required />
                            </div>
                            <div>
                                <Label htmlFor="end_time">End Time</Label>
                                <Input id="end_time" name="end_time" type="time" defaultValue={event.end_time} required />
                            </div>
                            <div>
                                <Label htmlFor="location">Location</Label>
                                <Input id="location" name="location" defaultValue={event.location} required />
                            </div>
                            <div>
                                <Label htmlFor="target_audience">Target Audience</Label>
                                <select id="target_audience" name="target_audience" className="input" required defaultValue={event.target_audience}>
                                    <option value="all">All</option>
                                    <option value="students">Students</option>
                                    <option value="teachers">Teachers</option>
                                    <option value="parents">Parents</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea id="description" name="description" className="input min-h-[100px]" defaultValue={event.description} />
                            </div>
                            <div>
                                <Label htmlFor="is_active">Active</Label>
                                <select id="is_active" name="is_active" className="input" required defaultValue={String(event.is_active)}>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/content/events">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Event</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
