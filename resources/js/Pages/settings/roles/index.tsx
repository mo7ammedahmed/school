import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Shield } from 'lucide-react';


export default function RolesIndex({ roles }: { roles: { id: number; name: string; guard_name: string; permissions_count: number }[] }) {
    const columns = [
        {
            accessorKey: 'name',
            header: 'Role Name',
            cell: ({ row }: any) => row.original.name.charAt(0).toUpperCase() + row.original.name.slice(1).replace('_', ' '),
        },
        {
            accessorKey: 'guard_name',
            header: 'Guard',
        },
        {
            accessorKey: 'permissions_count',
            header: 'Permissions',
            cell: ({ row }: any) => row.original.permissions_count ?? 0,
        },
    ];

    return (
        <AppShell
            title="Roles"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings' },
                { label: 'Roles' },
            ]}
        >
            <PageHeader
                title="Roles"
                description="Manage system roles"
                actions={
                    <Button>
                        <Shield className="mr-2 h-4 w-4" />
                        New Role
                    </Button>
                }
            />

            <DataTable columns={columns} data={roles} />
        </AppShell>
    );
}
