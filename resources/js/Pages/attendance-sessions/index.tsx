import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function AttendanceSessionsIndex({ sessions }: { sessions: { id: number; section: { name: string }; subject: { name: string }; teacher: { first_name: string; last_name: string }; session_date: string; start_time: string; end_time: string; status: string; is_finalized: boolean }[] }) {
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
            accessorKey: 'session_date',
            header: 'Date',
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
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'completed' ? 'default' : status === 'scheduled' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            accessorKey: 'is_finalized',
            header: 'Finalized',
            cell: ({ row }) => row.original.is_finalized ? 'Yes' : 'No',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/attendance-sessions/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/attendance-sessions/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Attendance Sessions"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Attendance', href: '/attendance' },
                { label: 'Sessions' },
            ]}
        >
            <PageHeader
                title="Attendance Sessions"
                description="Manage attendance sessions"
                actions={
                    <Button asChild>
                        <Link href="/attendance-sessions/create"><Plus className="mr-2 h-4 w-4" />New Session</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={sessions} />
        </AppShell>
    );
}
