import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function NewsIndex({ news }: { news: { id: number; title: string; category: string; publish_date: string; is_published: boolean }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
        },
        {
            accessorKey: 'category',
            header: 'Category',
            cell: ({ row }) => row.original.category.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase()),
        },
        {
            accessorKey: 'publish_date',
            header: 'Publish Date',
        },
        {
            accessorKey: 'is_published',
            header: 'Status',
            cell: ({ row }) => row.original.is_published ? 'Published' : 'Draft',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/content/news/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/content/news/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="News"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'News' },
            ]}
        >
            <PageHeader
                title="News"
                description="Manage news articles"
                actions={
                    <Button asChild>
                        <Link href="/content/news/create"><Plus className="mr-2 h-4 w-4" />New Article</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={news} />
        </AppShell>
    );
}
