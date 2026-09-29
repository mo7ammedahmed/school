import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';
import { useBilingual } from '@/lib/i18n/bilingual';

type FeeStructure = {
    id: number;
    amount: number;
    description: string | null;
    description_ar: string | null;
    fee_type: { name: string | null; name_ar: string | null } | null;
    grade_level: { name_en: string | null; name_ar: string | null } | null;
};

export default function FinanceFeeStructuresIndex({ feeStructures }: { feeStructures: { data: FeeStructure[] } }) {
    const bilingual = useBilingual();

    const columns: ColumnDef<FeeStructure>[] = [
        {
            id: 'description',
            header: 'Description',
            cell: ({ row }) => bilingual(row.original.description, row.original.description_ar),
        },
        {
            id: 'feeType',
            header: 'Fee Type',
            cell: ({ row }) => bilingual(row.original.fee_type?.name, row.original.fee_type?.name_ar),
        },
        {
            id: 'gradeLevel',
            header: 'Grade Level',
            cell: ({ row }) => bilingual(row.original.grade_level?.name_en, row.original.grade_level?.name_ar, 'Every grade'),
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
                { label: 'Finance', href: '/finance/invoices' },
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
