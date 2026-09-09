import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ColumnDef } from '@tanstack/react-table';

type Page = { id: number; title: string; slug: string; status: string; updated_at: string };

export default function PagesIndex({ pages }: { pages: { data: Page[] } }) {
    const columns: ColumnDef<Page>[] = [
        { accessorKey: 'title', header: 'Title' },
        { accessorKey: 'slug', header: 'Slug' },
        { accessorKey: 'status', header: 'Status' },
        { accessorKey: 'updated_at', header: 'Updated' },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <Button variant="ghost" size="sm" asChild>
                    <Link href={`/content/pages/${row.original.id}/edit`}>Edit</Link>
                </Button>
            ),
        },
    ];

    return (
        <AppShell title="Website Pages" breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Website Pages' }]}>
            <PageHeader
                title="Website Pages"
                description="Manage structured, school-scoped public pages."
                actions={<Button asChild><Link href="/content/pages/create"><Plus className="mr-2 h-4 w-4" />New page</Link></Button>}
            />
            <DataTable columns={columns} data={pages.data} />
        </AppShell>
    );
}
