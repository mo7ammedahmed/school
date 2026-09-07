import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function ReportCardsIndex({ reportCards }: { reportCards: { id: number; student: { first_name: string; last_name: string }; academic_year: { name: string }; grade: string; gpa: number; status: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'student.first_name',
            header: 'Student',
            cell: ({ row }) => `${row.original.student.first_name} ${row.original.student.last_name}`,
        },
        {
            accessorKey: 'academic_year.name',
            header: 'Academic Year',
        },
        {
            accessorKey: 'grade',
            header: 'Grade',
        },
        {
            accessorKey: 'gpa',
            header: 'GPA',
            cell: ({ row }) => Number(row.original.gpa).toFixed(2),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'published' ? 'default' : status === 'draft' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/report-cards/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/report-cards/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Report Cards"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards' },
            ]}
        >
            <PageHeader
                title="Report Cards"
                description="Manage student report cards"
                actions={
                    <Button asChild>
                        <Link href="/report-cards/create"><Plus className="mr-2 h-4 w-4" />New Report Card</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={reportCards} />
        </AppShell>
    );
}
