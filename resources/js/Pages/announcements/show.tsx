import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AnnouncementsShow({ announcement }: { announcement: { id: number; title: string; target_audience: string; publish_date: string; expiry_date: string; content: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Announcement Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements', href: '/announcements' },
                { label: announcement.title },
            ]}
        >
            <PageHeader
                title="Announcement Details"
                description={announcement.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/announcements"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/announcements/${announcement.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Announcement Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Title</span>
                            <p className="text-base">{announcement.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Target Audience</span>
                            <p className="text-base capitalize">{announcement.target_audience.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Publish Date</span>
                            <p className="text-base">{announcement.publish_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Expiry Date</span>
                            <p className="text-base">{announcement.expiry_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">{announcement.is_active ? 'Active' : 'Inactive'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Content</span>
                            <p className="text-base whitespace-pre-wrap">{announcement.content}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
