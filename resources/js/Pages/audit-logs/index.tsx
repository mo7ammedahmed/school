import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';

import { DataTable } from '@/components/ui/data-table';
import { ColumnDef } from '@tanstack/react-table';

export default function AuditLogsIndex({ auditLogs }: { auditLogs: { id: number; user: { name: string }; action: string; model_type: string; model_id: number; ip_address: string; created_at: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'user.name',
            header: 'User',
        },
        {
            accessorKey: 'action',
            header: 'Action',
            cell: ({ row }) => row.original.action.charAt(0).toUpperCase() + row.original.action.slice(1),
        },
        {
            accessorKey: 'model_type',
            header: 'Model',
            cell: ({ row }) => row.original.model_type.split('\\').pop(),
        },
        {
            accessorKey: 'model_id',
            header: 'Model ID',
        },
        {
            accessorKey: 'ip_address',
            header: 'IP Address',
        },
        {
            accessorKey: 'created_at',
            header: 'Timestamp',
            cell: ({ row }) => new Date(row.original.created_at).toLocaleString(),
        },
    ];

    return (
        <AppShell
            title="Audit Logs"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Audit Logs' },
            ]}
        >
            <PageHeader
                title="Audit Logs"
                description="Track system activities"
            />

            <DataTable columns={columns} data={auditLogs} />
        </AppShell>
    );
}
