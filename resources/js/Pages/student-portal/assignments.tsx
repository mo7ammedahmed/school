import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';

export default function StudentAssignments({ student, assignments }: { student: { first_name: string; last_name: string }; assignments: { title: string; due_date: string; status: string; offering: { subject: { name: string } } }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
        },
        {
            accessorKey: 'offering.subject.name',
            header: 'Subject',
        },
        {
            accessorKey: 'due_date',
            header: 'Due Date',
        },
        {
            accessorKey: 'status',
            header: 'Status',
        },
    ];

    return (
        <AppShell
            title="My Assignments"
            breadcrumbs={[
                { label: 'Student Portal', href: '/student/dashboard' },
                { label: 'Assignments' },
            ]}
        >
            <PageHeader
                title="My Assignments"
                description={`${student.first_name} ${student.last_name}'s assignments`}
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={assignments} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
