import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { AnnouncementForm, type AnnouncementDraft } from '@/components/announcements/announcement-form';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AnnouncementsEdit({ announcement }: { announcement: AnnouncementDraft }) {
    const heading = announcement.title ?? announcement.title_ar ?? '';

    return (
        <AppShell
            title="Edit Announcement"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements', href: '/announcements' },
                { label: 'Edit Announcement' },
            ]}
        >
            <PageHeader
                title="Edit Announcement"
                description={heading}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/announcements"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <AnnouncementForm
                announcement={announcement}
                action={`/announcements/${announcement.id}`}
                method="PUT"
                submitLabel="Update Announcement"
            />
        </AppShell>
    );
}
