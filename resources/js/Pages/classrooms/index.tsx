import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function ClassroomsIndex({ classrooms }: { classrooms: { id: number; name: string; section: { name: string }; teacher: { first_name: string; last_name: string }; capacity: number }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Classroom Name',
        },
        {
            accessorKey: 'section.name',
            header: 'Section',
        },
        {
            accessorKey: 'teacher.first_name',
            header: 'Teacher',
            cell: ({ row }) => `${row.original.teacher.first_name} ${row.original.teacher.last_name}`,
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
                        <Link href={`/classrooms/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/classrooms/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Classrooms"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Classrooms' },
            ]}
        >
            <PageHeader
                title="Classrooms"
                description="Manage classrooms"
                actions={
                    <Button asChild>
                        <Link href="/classrooms/create"><Plus className="mr-2 h-4 w-4" />New Classroom</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={classrooms} />
        </AppShell>
    );
}
