import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { AnnouncementForm } from '@/components/announcements/announcement-form';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AnnouncementsCreate() {
    return (
        <AppShell
            title="New Announcement"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements', href: '/announcements' },
                { label: 'New Announcement' },
            ]}
        >
            <PageHeader
                title="New Announcement"
                description="Create a new announcement"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/announcements"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <AnnouncementForm action="/announcements" submitLabel="Create Announcement" />
        </AppShell>
    );
}
