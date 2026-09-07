import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function SubjectsIndex({ subjects }: { subjects: { id: number; name: string; code: string; grade_level: { name: string } }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'code',
            header: 'Code',
        },
        {
            accessorKey: 'name',
            header: 'Subject Name',
        },
        {
            accessorKey: 'grade_level.name',
            header: 'Grade Level',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/subjects/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/subjects/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Subjects"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Subjects' },
            ]}
        >
            <PageHeader
                title="Subjects"
                description="Manage subjects and curriculum"
                actions={
                    <Button asChild>
                        <Link href="/subjects/create"><Plus className="mr-2 h-4 w-4" />New Subject</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={subjects} />
        </AppShell>
    );
}
