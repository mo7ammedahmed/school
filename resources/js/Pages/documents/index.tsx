import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';

type DocumentRow = {
    id: number;
    title: string;
    classification?: string | null;
    file_size?: string | null;
    uploadedBy?: { id: number; name: string } | null;
    created_at?: string | null;
};

export default function DocumentsIndex({ documents }: { documents: DocumentRow[] }) {
    const columns: ColumnDef<DocumentRow>[] = [
        {
            accessorKey: 'title',
            header: 'Document Name',
        },
        {
            accessorKey: 'classification',
            header: 'Type',
            cell: ({ row }) => {
                const documentType = row.original.classification?.trim();

                return documentType
                    ? documentType.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
                    : 'Unknown';
            },
        },
        {
            accessorKey: 'file_size',
            header: 'File Size',
            cell: ({ row }) => row.original.file_size || '-',
        },
        {
            accessorKey: 'uploadedBy',
            header: 'Uploaded By',
            cell: ({ row }) => row.original.uploadedBy?.name || '-',
        },
        {
            accessorKey: 'created_at',
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
