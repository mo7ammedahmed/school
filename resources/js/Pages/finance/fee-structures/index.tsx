import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export default function FinanceFeeStructuresIndex({ feeStructures }: { feeStructures: { id: number; name: string; fee_type: { name: string }; grade_level: { name: string }; amount: number }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
        },
        {
            accessorKey: 'fee_type.name',
            header: 'Fee Type',
        },
        {
            accessorKey: 'grade_level.name',
            header: 'Grade Level',
        },
        {
            accessorKey: 'amount',
            header: 'Amount',
            cell: ({ row }) => Number(row.original.amount).toFixed(2),
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/fee-structures/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/finance/fee-structures/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Fee Structures"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance' },
                { label: 'Fee Structures' },
            ]}
        >
            <PageHeader
                title="Fee Structures"
                description="Configure fee structures by grade"
                actions={
                    <Button asChild>
                        <Link href="/finance/fee-structures/create"><Plus className="mr-2 h-4 w-4" />New Fee Structure</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={feeStructures} />
        </AppShell>
    );
}
