import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function EventsShow({ event }: { event: { id: number; title: string; event_date: string; start_time: string; end_time: string; location: string; target_audience: string; description: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Event Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Events', href: '/events' },
                { label: event.title },
            ]}
        >
            <PageHeader
                title="Event Details"
                description={event.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/content/events"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/content/events/${event.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Event Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Event Title</span>
                            <p className="text-base">{event.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Event Date</span>
                            <p className="text-base">{event.event_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Time</span>
                            <p className="text-base">{event.start_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Time</span>
                            <p className="text-base">{event.end_time}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Location</span>
                            <p className="text-base">{event.location}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Target Audience</span>
                            <p className="text-base capitalize">{event.target_audience.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">{event.is_active ? 'Active' : 'Inactive'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="text-base whitespace-pre-wrap">{event.description || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
