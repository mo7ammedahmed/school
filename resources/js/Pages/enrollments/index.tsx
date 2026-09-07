import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function EnrollmentsIndex({ enrollments }: { enrollments: { id: number; student: { first_name: string; last_name: string }; section: { name: string }; academic_year: { name: string }; enrollment_date: string; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'student.first_name',
            header: 'Student',
            cell: ({ row }) => `${row.original.student.first_name} ${row.original.student.last_name}`,
        },
        {
            accessorKey: 'section.name',
            header: 'Section',
        },
        {
            accessorKey: 'academic_year.name',
            header: 'Academic Year',
        },
        {
            accessorKey: 'enrollment_date',
            header: 'Enrollment Date',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'active' ? 'default' : status === 'completed' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/enrollments/${row.original.id}`}>View</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Enrollments"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Enrollments' },
            ]}
        >
            <PageHeader
                title="Enrollments"
                description="Manage student enrollments"
                actions={
                    <Button asChild>
                        <Link href="/enrollments/create"><Plus className="mr-2 h-4 w-4" />New Enrollment</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={enrollments} />
        </AppShell>
    );
}
