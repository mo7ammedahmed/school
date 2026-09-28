import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { humaniseRole } from '@/lib/utils';

export default function RolesIndex({ roles }: { roles: { id: number; name: string; guard_name: string; permissions_count: number }[] }) {
    const columns = [
        {
            accessorKey: 'name',
            header: 'Role Name',
            cell: ({ row }: any) => humaniseRole(row.original.name),
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
                { label: 'Settings', href: '/settings/school' },
                { label: 'Roles' },
            ]}
        >
            <PageHeader
                title="Roles"
                description="Roles are assigned to users from the Settings → Users screen."
            />

            <DataTable columns={columns} data={roles} />
        </AppShell>
    );
}
