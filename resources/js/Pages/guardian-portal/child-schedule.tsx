import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardianChildSchedule({ child, timetable }: { child: { first_name: string; last_name: string }; timetable: { day_of_week: string; start_time: string; end_time: string; offering?: { subject?: { name: string } }; teacher?: { user?: { name: string } }; room?: { name: string } }[] }) {
    const columns: ColumnDef<any>[] = [
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
            title="Child Schedule"
            breadcrumbs={[
                { label: 'Guardian Portal', href: '/guardian' },
                { label: 'Children', href: '/guardian/children' },
                { label: `${child.first_name}'s Schedule` },
            ]}
        >
            <PageHeader
                title={`${child.first_name} ${child.last_name}'s Schedule`}
                description="Weekly class timetable"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/guardian/children"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={timetable} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
