import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable, type Paginator } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';

type Announcement = {
    id: number;
    title: string | null;
    title_ar: string | null;
    target_audience: string | null;
    start_date: string;
    end_date: string;
    is_published: boolean;
};

export default function AnnouncementsIndex({ announcements }: { announcements: Paginator<Announcement> }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
            cell: ({ row }) => row.original.title ?? row.original.title_ar ?? '—',
        },
        {
            accessorKey: 'target_audience',
            header: 'Audience',
            cell: ({ row }) =>
                (row.original.target_audience ?? '—').replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'start_date',
            header: 'Start Date',
            cell: ({ row }) => row.original.start_date?.slice(0, 10) ?? '—',
        },
        {
            accessorKey: 'end_date',
            header: 'End Date',
            cell: ({ row }) => row.original.end_date?.slice(0, 10) ?? '—',
        },
        {
            accessorKey: 'is_published',
            header: 'Status',
            cell: ({ row }) => row.original.is_published ? <Badge>Published</Badge> : <Badge variant="secondary">Draft</Badge>,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/announcements/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/announcements/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Announcements"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements' },
            ]}
        >
            <PageHeader
                title="Announcements"
                description="Manage announcements"
                actions={
                    <Button asChild>
                        <Link href="/announcements/create"><Plus className="me-2 h-4 w-4" />New Announcement</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={announcements} />
        </AppShell>
    );
}
