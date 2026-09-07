import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function AcademicYearsIndex({ academicYears }: { academicYears: { id: number; name: string; start_date: string; end_date: string; is_current: boolean }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Academic Year',
        },
        {
            accessorKey: 'start_date',
            header: 'Start Date',
        },
        {
            accessorKey: 'end_date',
            header: 'End Date',
        },
        {
            accessorKey: 'is_current',
            header: 'Status',
            cell: ({ row }) => row.original.is_current ? <Badge>Current</Badge> : <Badge variant="secondary">Past</Badge>,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/academic-years/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/academic-years/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Academic Years"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Academic Years' },
            ]}
        >
            <PageHeader
                title="Academic Years"
                description="Manage academic year periods"
                actions={
                    <Button asChild>
                        <Link href="/academic-years/create"><Plus className="mr-2 h-4 w-4" />New Academic Year</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={academicYears} />
        </AppShell>
    );
}
