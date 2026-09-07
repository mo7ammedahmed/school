import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function AttendanceIndex({ attendances }: { attendances: { id: number; student: { first_name: string; last_name: string }; date: string; status: string; remarks: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'student.first_name',
            header: 'Student',
            cell: ({ row }) => `${row.original.student.first_name} ${row.original.student.last_name}`,
        },
        {
            accessorKey: 'date',
            header: 'Date',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'present' ? 'default' : status === 'absent' ? 'destructive' : 'secondary';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            accessorKey: 'remarks',
            header: 'Remarks',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/attendance/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/attendance/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Attendance"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Attendance' },
            ]}
        >
            <PageHeader
                title="Attendance"
                description="Track student attendance"
                actions={
                    <Button asChild>
                        <Link href="/attendance/create"><Plus className="mr-2 h-4 w-4" />Mark Attendance</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={attendances} />
        </AppShell>
    );
}
