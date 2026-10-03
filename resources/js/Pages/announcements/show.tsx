import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type Announcement = {
    id: number;
    title: string | null;
    title_ar: string | null;
    body: string | null;
    body_ar: string | null;
    target_audience: string | null;
    start_date: string;
    end_date: string;
    is_published: boolean;
};

export default function AnnouncementsShow({ announcement }: { announcement: Announcement }) {
    return (
        <AppShell
            title="Announcement Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements', href: '/announcements' },
                { label: announcement.title ?? announcement.title_ar ?? '' },
            ]}
        >
            <PageHeader
                title="Announcement Details"
                description={announcement.title ?? announcement.title_ar ?? ''}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/announcements"><ArrowLeft className="me-2 h-4 w-4" />Back</Link>
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
                            <span className="text-sm font-medium text-muted-foreground">Title (English)</span>
                            <p className="text-base" dir="ltr">{announcement.title ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Title (Arabic)</span>
                            <p className="text-base" dir="rtl">{announcement.title_ar ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Target Audience</span>
                            <p className="text-base capitalize">{(announcement.target_audience ?? '—').replace('_', ' ')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">{announcement.is_published ? 'Published' : 'Draft'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Date</span>
                            <p className="text-base">{announcement.start_date?.slice(0, 10) ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Date</span>
                            <p className="text-base">{announcement.end_date?.slice(0, 10) ?? '—'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Body (English)</span>
                            <p className="text-base whitespace-pre-wrap" dir="ltr">{announcement.body ?? '—'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Body (Arabic)</span>
                            <p className="text-base whitespace-pre-wrap" dir="rtl">{announcement.body_ar ?? '—'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
