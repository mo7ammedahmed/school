import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function MaterialsIndex({ materials }: {    materials: { id: number; title: string; subject: { name: string }; section: { name: string }; file_type: string; file_size: number; uploaded_at: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
        },
        {
            accessorKey: 'subject.name',
            header: 'Subject',
        },
        {
            accessorKey: 'section.name',
            header: 'Section',
        },
        {
            accessorKey: 'file_type',
            header: 'File Type',
            cell: ({ row }) => row.original.file_type ? row.original.file_type.toUpperCase() : '—',
        },
        {
            accessorKey: 'file_size',
            header: 'Size',
            cell: ({ row }) => (row.original.file_size ? `${(row.original.file_size / 1024).toFixed(1)} KB` : '—'),
        },
        {
            accessorKey: 'uploaded_at',
            header: 'Uploaded At',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/materials/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/materials/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Materials"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Materials' },
            ]}
        >
            <PageHeader
                title="Materials"
                description="Manage learning materials"
                actions={
                    <Button asChild>
                        <Link href="/materials/create"><Plus className="mr-2 h-4 w-4" />Upload Material</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={materials} />
        </AppShell>
    );
}
