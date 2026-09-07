import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardianChildAttendance({ child, records }: { child: { first_name: string; last_name: string }; records: { status: string; notes?: string; attendance_session?: { session_date: string } }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            id: 'date',
            header: 'Date',
            accessorFn: (row) => row.attendance_session?.session_date ?? '—',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const colors: Record<string, string> = {
                    present: 'text-green-600',
                    absent: 'text-red-600',
                    late: 'text-yellow-600',
                    excused: 'text-blue-600',
                };
                return <span className={colors[status] || 'text-gray-600'}>{status}</span>;
            },
        },
        {
            accessorKey: 'notes',
            header: 'Notes',
        },
    ];

    return (
        <AppShell
            title="Child Attendance"
            breadcrumbs={[
                { label: 'Guardian Portal', href: '/guardian' },
                { label: 'Children', href: '/guardian/children' },
                { label: `${child.first_name}'s Attendance` },
            ]}
        >
            <PageHeader
                title={`${child.first_name} ${child.last_name}'s Attendance`}
                description="Attendance records"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/guardian/children"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={records} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
