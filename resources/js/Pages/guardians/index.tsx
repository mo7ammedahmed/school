import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function GuardiansIndex({ guardians }: { guardians: { id: number; first_name: string; last_name: string; email: string; phone: string; relationship: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'first_name',
            header: 'First Name',
        },
        {
            accessorKey: 'last_name',
            header: 'Last Name',
        },
        {
            accessorKey: 'email',
            header: 'Email',
        },
        {
            accessorKey: 'phone',
            header: 'Phone',
        },
        {
            accessorKey: 'relationship',
            header: 'Relationship',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/guardians/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/guardians/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Guardians"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Guardians' },
            ]}
        >
            <PageHeader
                title="Guardians"
                description="Manage parent and guardian records"
                actions={
                    <Button asChild>
                        <Link href="/guardians/create"><Plus className="mr-2 h-4 w-4" />New Guardian</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={guardians} />
        </AppShell>
    );
}
