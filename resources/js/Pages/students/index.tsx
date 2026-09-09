import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function StudentsIndex({ students }: { students: { id: number; first_name: string; last_name: string; email: string; student_id_number: string; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'student_id_number',
            header: 'Enrollment #',
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
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'active' ? 'default' : status === 'graduated' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/students/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/students/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Students"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Students' },
            ]}
        >
            <PageHeader
                title="Students"
                description="Manage student records"
                actions={
                    <Button asChild>
                        <Link href="/students/create"><Plus className="mr-2 h-4 w-4" />New Student</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={students} />
        </AppShell>
    );
}
