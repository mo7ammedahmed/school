import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function AssessmentsIndex({ assessments }: { assessments: { id: number; name: string; assessment_type: string; section: { name: string }; assessment_date: string; total_marks: number; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
        },
        {
            accessorKey: 'assessment_type',
            header: 'Type',
            cell: ({ row }) => row.original.assessment_type.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'section.name',
            header: 'Section',
        },
        {
            accessorKey: 'assessment_date',
            header: 'Date',
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
                        <Link href={`/assessments/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/assessments/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Assessments"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assessments' },
            ]}
        >
            <PageHeader
                title="Assessments"
                description="Manage assessments"
                actions={
                    <Button asChild>
                        <Link href="/assessments/create"><Plus className="mr-2 h-4 w-4" />New Assessment</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={assessments} />
        </AppShell>
    );
}
