import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable, type Paginator } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';
import { humaniseRole } from '@/lib/utils';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    last_login_at: string | null;
};

export default function UsersIndex({ users }: { users: UserRow[] | Paginator<UserRow> }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
        },
        {
            accessorKey: 'email',
            header: 'Email',
        },
        {
            accessorKey: 'role',
            header: 'Role',
            cell: ({ row }) => humaniseRole(row.original.role),
        },
        {
            accessorKey: 'is_active',
            header: 'Status',
            cell: ({ row }) =>
                row.original.is_active ? <Badge>Active</Badge> : <Badge variant="destructive">Inactive</Badge>,
        },
        {
            accessorKey: 'last_login_at',
            header: 'Last Login',
            cell: ({ row }) => row.original.last_login_at ? new Date(row.original.last_login_at).toLocaleString() : 'Never',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/settings/users/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/settings/users/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Users"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Users' },
            ]}
        >
            <PageHeader
                title="Users"
                description="Manage system users"
                actions={
                    <Button asChild>
                        <Link href="/settings/users/create"><Plus className="mr-2 h-4 w-4" />New User</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={users} />
        </AppShell>
    );
}
