import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable, type Paginator } from '@/components/ui/data-table';
import { type ColumnDef } from '@/lib/table';

type AuditLogRow = {
    id: number;
    user: { name: string } | null;
    action: string;
    entity_type: string | null;
    entity_id: number | null;
    ip_address: string | null;
    created_at: string | null;
};

export default function AuditLogsIndex({ auditLogs }: { auditLogs: Paginator<AuditLogRow> }) {
    const columns: ColumnDef<AuditLogRow>[] = [
        {
            id: 'user',
            header: 'User',
            cell: ({ row }) => row.original.user?.name ?? 'System',
        },
        {
            accessorKey: 'action',
            header: 'Action',
            cell: ({ row }) => row.original.action.charAt(0).toUpperCase() + row.original.action.slice(1),
        },
        {
            accessorKey: 'entity_type',
            header: 'Entity',
            cell: ({ row }) => (row.original.entity_type ? row.original.entity_type.split('\\').pop() : '—'),
        },
        {
            accessorKey: 'entity_id',
            header: 'Entity ID',
            cell: ({ row }) => row.original.entity_id ?? '—',
        },
        {
            accessorKey: 'ip_address',
            header: 'IP Address',
            cell: ({ row }) => row.original.ip_address ?? '—',
        },
        {
            accessorKey: 'created_at',
            header: 'Timestamp',
            cell: ({ row }) =>
                row.original.created_at ? new Date(row.original.created_at).toLocaleString() : '—',
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
            <PageHeader title="Audit Logs" description="Track system activities" />

            <DataTable columns={columns} data={auditLogs} emptyMessage="No audit log entries yet." />
        </AppShell>
    );
}
