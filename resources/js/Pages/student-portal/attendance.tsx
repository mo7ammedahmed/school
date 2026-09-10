import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ColumnDef } from '@tanstack/react-table';

interface Record_ {
    id: number;
    status: string;
    notes?: string;
    attendance_session?: { session_date: string };
}

export default function StudentAttendance({ student, records }: { student: { first_name: string; last_name: string }; records: Record_[] }) {
    const columns: ColumnDef<Record_, any>[] = [
        {
            id: 'date',
            header: 'Date',
            accessorFn: (row) => row.attendance_session?.session_date ?? '—',
            cell: ({ row }) => {
                const d = row.original.attendance_session?.session_date;
                return d ? new Date(d).toLocaleDateString() : '—';
            },
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant =
                    status === 'present'
                        ? 'success'
                        : status === 'absent'
                          ? 'destructive'
                          : status === 'late'
                            ? 'warning'
                            : 'info';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            accessorKey: 'notes',
            header: 'Notes',
            cell: ({ row }) => row.original.notes ?? '—',
        },
    ];

    return (
        <AppShell
            title="My Attendance"
            breadcrumbs={[
                { label: 'Student Portal', href: '/student/dashboard' },
                { label: 'Attendance' },
            ]}
        >
            <PageHeader
                title="My Attendance"
                description={`${student.first_name} ${student.last_name}'s attendance records`}
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={records} emptyMessage="No attendance records yet." />
                </CardContent>
            </Card>
        </AppShell>
    );
}
