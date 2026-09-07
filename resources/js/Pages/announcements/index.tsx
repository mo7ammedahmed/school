import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function AnnouncementsIndex({ announcements }: { announcements: { id: number; title: string; target_audience: string; publish_date: string; expiry_date: string; is_active: boolean }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
        },
        {
            accessorKey: 'target_audience',
            header: 'Audience',
            cell: ({ row }) => row.original.target_audience.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'publish_date',
            header: 'Publish Date',
        },
        {
            accessorKey: 'expiry_date',
            header: 'Expiry Date',
        },
        {
            accessorKey: 'is_active',
            header: 'Status',
            cell: ({ row }) => row.original.is_active ? <Badge>Active</Badge> : <Badge variant="secondary">Inactive</Badge>,
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
                        <Link href="/announcements/create"><Plus className="mr-2 h-4 w-4" />New Announcement</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={announcements} />
        </AppShell>
    );
}
