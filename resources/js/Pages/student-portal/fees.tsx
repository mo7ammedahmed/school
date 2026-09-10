import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';

export default function StudentFees({ student, invoices }: { student: { first_name: string; last_name: string }; invoices: { id: number; invoice_number: string; total_amount: number; currency: string; status: string; due_date: string }[] }) {
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
            title="My Fees"
            breadcrumbs={[
                { label: 'Student Portal', href: '/student/dashboard' },
                { label: 'Fees' },
            ]}
        >
            <PageHeader
                title="My Fees"
                description={`${student.first_name} ${student.last_name}'s fee summary`}
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={invoices} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
