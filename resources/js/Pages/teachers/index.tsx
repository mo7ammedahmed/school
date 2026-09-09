import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function TeachersIndex({ teachers }: { teachers: { id: number; first_name: string; last_name: string; email: string; employee_id: string; specialization: string; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'employee_id',
            header: 'Employee ID',
        },
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
            accessorKey: 'specialization',
            header: 'Specialization',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = typeof row.original.status === 'string'
                    ? row.original.status
                    : 'unknown';
                const variant = status === 'active' ? 'default' : status === 'on_leave' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status.replace('_', ' ')}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/teachers/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/teachers/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Teachers"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Teachers' },
            ]}
        >
            <PageHeader
                title="Teachers"
                description="Manage teaching staff"
                actions={
                    <Button asChild>
                        <Link href="/teachers/create"><Plus className="mr-2 h-4 w-4" />New Teacher</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={teachers} />
        </AppShell>
    );
}
