import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';

type AssessmentRow = {
    id: number;
    name: string;
    category: string | null;
    subject: string | null;
    section: string | null;
    due_date: string | null;
    max_score: number | null;
    weight: number | null;
    is_published: boolean;
};

export default function AssessmentsIndex({ assessments }: { assessments: { data: AssessmentRow[] } }) {
    const columns: ColumnDef<AssessmentRow>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
        },
        {
            accessorKey: 'category',
            header: 'Category',
            cell: ({ row }) => row.original.category ?? '—',
        },
        {
            accessorKey: 'subject',
            header: 'Subject',
            cell: ({ row }) => row.original.subject ?? '—',
        },
        {
            accessorKey: 'section',
            header: 'Section',
            cell: ({ row }) => row.original.section ?? '—',
        },
        {
            accessorKey: 'due_date',
            header: 'Due Date',
            cell: ({ row }) => row.original.due_date ?? '—',
        },
        {
            accessorKey: 'max_score',
            header: 'Max Score',
            cell: ({ row }) => (row.original.max_score === null ? '—' : row.original.max_score.toFixed(2)),
        },
        {
            accessorKey: 'weight',
            header: 'Weight',
            cell: ({ row }) => (row.original.weight === null ? '—' : `${row.original.weight}%`),
        },
        {
            accessorKey: 'is_published',
            header: 'Status',
            cell: ({ row }) =>
                row.original.is_published ? <Badge>Published</Badge> : <Badge variant="secondary">Draft</Badge>,
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
                        <Link href={`/assessments/${row.original.id}/scores`}>Scores</Link>
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
                description="Manage assessments and their weightings"
                actions={
                    <Button asChild>
                        <Link href="/assessments/create">
                            <Plus className="me-2 h-4 w-4" />
                            New Assessment
                        </Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={assessments} emptyMessage="No assessments yet." />
        </AppShell>
    );
}
