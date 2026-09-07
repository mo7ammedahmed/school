import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function QuizzesIndex({ quizzes }: {    quizzes: { id: number; title: string; subject: { name: string }; section: { name: string }; duration_minutes: number; total_marks: number; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
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
            accessorKey: 'duration_minutes',
            header: 'Duration (min)',
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
                const variant = status === 'published' ? 'default' : status === 'draft' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/quizzes/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/quizzes/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Quizzes"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Quizzes' },
            ]}
        >
            <PageHeader
                title="Quizzes"
                description="Manage quizzes"
                actions={
                    <Button asChild>
                        <Link href="/quizzes/create"><Plus className="mr-2 h-4 w-4" />New Quiz</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={quizzes} />
        </AppShell>
    );
}
