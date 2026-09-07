import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function EventsIndex({ events }: { events: { id: number; title: string; event_date: string; location: string; target_audience: string; is_active: boolean }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Event Title',
        },
        {
            accessorKey: 'event_date',
            header: 'Date',
        },
        {
            accessorKey: 'location',
            header: 'Location',
        },
        {
            accessorKey: 'target_audience',
            header: 'Audience',
            cell: ({ row }) => row.original.target_audience.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'is_active',
            header: 'Status',
            cell: ({ row }) => row.original.is_active ? 'Active' : 'Inactive',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/content/events/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/content/events/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Events"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Events' },
            ]}
        >
            <PageHeader
                title="Events"
                description="Manage school events"
                actions={
                    <Button asChild>
                        <Link href="/content/events/create"><Plus className="mr-2 h-4 w-4" />New Event</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={events} />
        </AppShell>
    );
}
