import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function FinanceDiscountsIndex({ discounts }: { discounts: { id: number; name: string; type: string; value: number; is_active: boolean; start_date: string; end_date: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
        },
        {
            accessorKey: 'type',
            header: 'Type',
            cell: ({ row }) => row.original.type.charAt(0).toUpperCase() + row.original.type.slice(1),
        },
        {
            accessorKey: 'value',
            header: 'Value',
            cell: ({ row }) => row.original.type === 'percentage' ? `${row.original.value}%` : Number(row.original.value).toFixed(2),
        },
        {
            accessorKey: 'is_active',
            header: 'Status',
            cell: ({ row }) => row.original.is_active ? <Badge>Active</Badge> : <Badge variant="secondary">Inactive</Badge>,
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
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/discounts/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/discounts/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Discounts"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Discounts' },
            ]}
        >
            <PageHeader
                title="Discounts"
                description="Manage discounts and promotions"
                actions={
                    <Button asChild>
                        <Link href="/finance/discounts/create"><Plus className="mr-2 h-4 w-4" />New Discount</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={discounts} />
        </AppShell>
    );
}
