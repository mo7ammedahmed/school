import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function DocumentsIndex({ documents }: { documents: { id: number; name: string; document_type: string; file_size: string; uploaded_by: string; uploaded_at: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Document Name',
        },
        {
            accessorKey: 'document_type',
            header: 'Type',
            cell: ({ row }) => row.original.document_type.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'file_size',
            header: 'File Size',
            cell: ({ row }) => row.original.file_size || '-',
        },
        {
            accessorKey: 'uploaded_by',
            header: 'Uploaded By',
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
                        <Link href={`/documents/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/documents/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Documents"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Documents' },
            ]}
        >
            <PageHeader
                title="Documents"
                description="Manage documents"
                actions={
                    <Button asChild>
                        <Link href="/documents/create"><Plus className="mr-2 h-4 w-4" />Upload Document</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={documents} />
        </AppShell>
    );
}
