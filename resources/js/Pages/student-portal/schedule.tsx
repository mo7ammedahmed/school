import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';

interface Entry {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    offering?: { subject?: { name: string } };
    teacher?: { user?: { name: string }; first_name: string; last_name: string };
    room?: { name: string };
}

export default function StudentSchedule({ student, timetable }: { student: { first_name: string; last_name: string }; timetable: Entry[] }) {
    const columns: ColumnDef<Entry, any>[] = [
        {
            accessorKey: 'day_of_week',
            header: 'Day',
            cell: ({ row }) => {
                const day = row.original.day_of_week;
                return day ? day.charAt(0).toUpperCase() + day.slice(1) : '—';
            },
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
            id: 'subject',
            header: 'Subject',
            accessorFn: (row) => row.offering?.subject?.name ?? '—',
        },
        {
            id: 'teacher',
            header: 'Teacher',
            accessorFn: (row) => row.teacher?.user?.name ?? '—',
        },
        {
            id: 'room',
            header: 'Room',
            accessorFn: (row) => row.room?.name ?? '—',
        },
    ];

    return (
        <AppShell
            title="My Schedule"
            breadcrumbs={[
                { label: 'Student Portal', href: '/student/dashboard' },
                { label: 'Schedule' },
            ]}
        >
            <PageHeader
                title="My Schedule"
                description={`${student.first_name} ${student.last_name}'s timetable`}
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={timetable} emptyMessage="No schedule published yet." />
                </CardContent>
            </Card>
        </AppShell>
    );
}
