import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function SubmissionsIndex({ submissions }: { submissions: { id: number; assignment: { title: string }; student: { first_name: string; last_name: string }; submitted_at: string; score: number; feedback: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'assignment.title',
            header: 'Assignment',
        },
        {
            accessorKey: 'student.first_name',
            header: 'Student',
            cell: ({ row }) => `${row.original.student.first_name} ${row.original.student.last_name}`,
        },
        {
            accessorKey: 'submitted_at',
            header: 'Submitted At',
        },
        {
            accessorKey: 'score',
            header: 'Score',
            cell: ({ row }) => row.original.score != null ? Number(row.original.score).toFixed(2) : '-',
        },
        {
            accessorKey: 'feedback',
            header: 'Feedback',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/submissions/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/submissions/${row.original.id}/edit`}>Grade</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Submissions"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assignments', href: '/assignments' },
                { label: 'Submissions' },
            ]}
        >
            <PageHeader
                title="Submissions"
                description="Manage assignment submissions"
                actions={
                    <Button asChild>
                        <Link href="/submissions/create"><Plus className="mr-2 h-4 w-4" />New Submission</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={submissions} />
        </AppShell>
    );
}
