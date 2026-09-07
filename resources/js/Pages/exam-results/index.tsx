import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function ExamResultsIndex({ results }: { results: { id: number; exam: { name: string }; student: { first_name: string; last_name: string }; score: number; notes: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'exam.name',
            header: 'Exam',
        },
        {
            accessorKey: 'student.first_name',
            header: 'Student',
            cell: ({ row }) => `${row.original.student.first_name} ${row.original.student.last_name}`,
        },
        {
            accessorKey: 'score',
            header: 'Score',
            cell: ({ row }) => Number(row.original.score).toFixed(2),
        },
        {
            accessorKey: 'notes',
            header: 'Notes',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/exam-results/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/exam-results/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Exam Results"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams', href: '/exams' },
                { label: 'Results' },
            ]}
        >
            <PageHeader
                title="Exam Results"
                description="Manage exam results"
                actions={
                    <Button asChild>
                        <Link href="/exam-results/create"><Plus className="mr-2 h-4 w-4" />Add Result</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={results} />
        </AppShell>
    );
}
