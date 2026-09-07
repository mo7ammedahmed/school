import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function GradeLevelsIndex({ gradeLevels }: { gradeLevels: { id: number; name: string; level: number; description: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Grade Name',
        },
        {
            accessorKey: 'level',
            header: 'Level',
        },
        {
            accessorKey: 'description',
            header: 'Description',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/grade-levels/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/grade-levels/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Grade Levels"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Grade Levels' },
            ]}
        >
            <PageHeader
                title="Grade Levels"
                description="Manage grade levels"
                actions={
                    <Button asChild>
                        <Link href="/grade-levels/create"><Plus className="mr-2 h-4 w-4" />New Grade Level</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={gradeLevels} />
        </AppShell>
    );
}
