import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function TimetableIndex({ timetables }: { timetables: { id: number; section: { name: string }; subject: { name: string }; teacher: { first_name: string; last_name: string }; day_of_week: string; start_time: string; end_time: string;    room: { name: string } | null }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'section.name',
            header: 'Section',
        },
        {
            accessorKey: 'subject.name',
            header: 'Subject',
        },
        {
            accessorKey: 'teacher.first_name',
            header: 'Teacher',
            cell: ({ row }) => `${row.original.teacher.first_name} ${row.original.teacher.last_name}`,
        },
        {
            accessorKey: 'day_of_week',
            header: 'Day',
            cell: ({ row }) => row.original.day_of_week.charAt(0).toUpperCase() + row.original.day_of_week.slice(1),
        },
        {
            accessorKey: 'start_time',
            header: 'Start Time',
        },
        {
            accessorKey: 'end_time',
            header: 'End Time',
        },
        {
            accessorKey: 'room',
            header: 'Room',
            cell: ({ row }) => row.original.room?.name ?? '—',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/timetable/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/timetable/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Timetable"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable' },
            ]}
        >
            <PageHeader
                title="Timetable"
                description="Manage class schedules"
                actions={
                    <Button asChild>
                        <Link href="/timetable/create"><Plus className="mr-2 h-4 w-4" />Add Schedule</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={timetables} />
        </AppShell>
    );
}
