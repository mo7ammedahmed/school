import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardianChildFees({ child, invoices }: { child: { first_name: string; last_name: string }; invoices: { id: number; invoice_number: string; total_amount: number; currency: string; status: string; due_date: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'invoice_number',
            header: 'Invoice',
        },
        {
            accessorKey: 'total_amount',
            header: 'Total',
            cell: ({ row }) => new Intl.NumberFormat('en', { style: 'currency', currency: row.original.currency }).format(Number(row.original.total_amount)),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const colors: Record<string, string> = {
                    draft: 'text-gray-600',
                    issued: 'text-blue-600',
                    partially_paid: 'text-yellow-600',
                    paid: 'text-green-600',
                    voided: 'text-red-600',
                };
                return <span className={colors[status] || 'text-gray-600'}>{status}</span>;
            },
        },
        {
            accessorKey: 'due_date',
            header: 'Due Date',
        },
    ];

    return (
        <AppShell
            title="Child Fees"
            breadcrumbs={[
                { label: 'Guardian Portal', href: '/guardian' },
                { label: 'Children', href: '/guardian/children' },
                { label: `${child.first_name}'s Fees` },
            ]}
        >
            <PageHeader
                title={`${child.first_name} ${child.last_name}'s Fees`}
                description="Fee summary and invoices"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/guardian/children"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={invoices} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
