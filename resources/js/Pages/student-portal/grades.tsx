import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';

export default function StudentGrades({ student, reportCards }: { student: { first_name: string; last_name: string }; reportCards: { id: number; academic_year?: { name: string }; gpa?: number; comments?: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            id: 'year',
            accessorFn: (row) => row.academic_year?.name ?? '—',
            header: 'Academic Year',
        },
        {
            accessorKey: 'gpa',
            header: 'GPA',
            cell: ({ row }) => Number(row.original.gpa).toFixed(2),
        },
        {
            accessorKey: 'comments',
            header: 'Comments',
            cell: ({ row }) => row.original.comments ?? '—',
        },
    ];

    return (
        <AppShell
            title="My Grades"
            breadcrumbs={[
                { label: 'Student Portal', href: '/student/dashboard' },
                { label: 'Grades' },
            ]}
        >
            <PageHeader
                title="My Grades"
                description={`${student.first_name} ${student.last_name}'s report cards`}
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={reportCards} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
