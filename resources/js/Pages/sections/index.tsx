import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function SectionsIndex({ sections }: { sections: { id: number; name: string; grade_level: { name: string }; academic_year: { name: string }; capacity: number }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Section Name',
        },
        {
            accessorKey: 'grade_level.name',
            header: 'Grade Level',
        },
        {
            accessorKey: 'academic_year.name',
            header: 'Academic Year',
        },
        {
            accessorKey: 'capacity',
            header: 'Capacity',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/sections/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/sections/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Sections"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Sections' },
            ]}
        >
            <PageHeader
                title="Sections"
                description="Manage class sections"
                actions={
                    <Button asChild>
                        <Link href="/sections/create"><Plus className="mr-2 h-4 w-4" />New Section</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={sections} />
        </AppShell>
    );
}
