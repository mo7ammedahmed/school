import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function ExamsIndex({ exams }: { exams: { id: number; name: string; subject: { name: string }; section: { name: string }; exam_date: string; total_marks: number; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Exam Name',
        },
        {
            accessorKey: 'subject.name',
            header: 'Subject',
        },
        {
            accessorKey: 'section.name',
            header: 'Section',
        },
        {
            accessorKey: 'exam_date',
            header: 'Exam Date',
        },
        {
            accessorKey: 'total_marks',
            header: 'Total Marks',
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
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/exams/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/exams/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Exams"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams' },
            ]}
        >
            <PageHeader
                title="Exams"
                description="Manage exams and assessments"
                actions={
                    <Button asChild>
                        <Link href="/exams/create"><Plus className="mr-2 h-4 w-4" />New Exam</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={exams} />
        </AppShell>
    );
}
