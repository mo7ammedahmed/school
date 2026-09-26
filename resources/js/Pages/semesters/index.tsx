import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/ui/data-table';
import { type ColumnDef } from '@/lib/table';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

type Semester = {
    id: number;
    name: string;
    name_ar: string | null;
    name_en: string | null;
    code: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    academic_year?: { name: string } | null;
};

export default function SemestersIndex({ semesters }: { semesters: Semester[] }) {
    const columns: ColumnDef<Semester, any>[] = [
        {
            accessorKey: 'name',
            header: 'Semester',
        },
        {
            id: 'academic_year',
            header: 'Academic Year',
            accessorFn: (row) => row.academic_year?.name ?? '—',
        },
        {
            accessorKey: 'code',
            header: 'Code',
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
            cell: ({ row }) =>
                row.original.is_current ? (
                    <Badge>Current</Badge>
                ) : (
                    <Badge variant="secondary">Inactive</Badge>
                ),
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/semesters/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/semesters/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Semesters"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Academic Years', href: '/academic-years' },
                { label: 'Semesters' },
            ]}
        >
            <PageHeader
                title="Semesters"
                description="Manage semesters for the academic year"
                actions={
                    <Button asChild>
                        <Link href="/semesters/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Add Semester
                        </Link>
                    </Button>
                }
            />

            <DataTable
                columns={columns}
                data={semesters}
                emptyMessage="No semesters yet. Add your first semester to start scheduling."
            />
        </AppShell>
    );
}
