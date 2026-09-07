import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function RoomsIndex({ rooms }: { rooms: { id: number; name: string; code: string; room_type: string; capacity: number }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Room Name',
        },
        {
            accessorKey: 'code',
            header: 'Code',
        },
        {
            accessorKey: 'room_type',
            header: 'Type',
            cell: ({ row }) => row.original.room_type.charAt(0).toUpperCase() + row.original.room_type.slice(1),
        },
        {
            accessorKey: 'capacity',
            header: 'Capacity',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/rooms/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/rooms/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Rooms"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Rooms' },
            ]}
        >
            <PageHeader
                title="Rooms"
                description="Manage school rooms and facilities"
                actions={
                    <Button asChild>
                        <Link href="/rooms/create"><Plus className="mr-2 h-4 w-4" />Add Room</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={rooms} />
        </AppShell>
    );
}
