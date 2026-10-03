import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Paginator } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';
import { formatDuration } from '@/lib/media/format';

type LiveSession = {
    id: number;
    title: string;
    status: string;
    recording_status: string;
    duration_seconds: number | null;
    started_at: string | null;
    offering: { subject: { name: string } | null; section: { name: string } | null } | null;
    material: { id: number } | null;
};

export default function LiveIndex({ sessions }: { sessions: Paginator<LiveSession> }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
        },
        {
            id: 'subject',
            header: 'Subject',
            cell: ({ row }) => row.original.offering?.subject?.name ?? '—',
        },
        {
            id: 'section',
            header: 'Section',
            cell: ({ row }) => row.original.offering?.section?.name ?? '—',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) =>
                row.original.status === 'live' ? (
                    <Badge variant="success">Live now</Badge>
                ) : row.original.status === 'ended' ? (
                    <Badge variant="neutral">Ended</Badge>
                ) : (
                    <Badge variant="secondary">Draft</Badge>
                ),
        },
        {
            accessorKey: 'recording_status',
            header: 'Recording',
            cell: ({ row }) =>
                row.original.recording_status === 'ready' ? (
                    <Badge variant="success">Ready</Badge>
                ) : row.original.recording_status === 'failed' ? (
                    <Badge variant="error">Failed</Badge>
                ) : row.original.status === 'ended' ? (
                    <Badge variant="warning">Preparing</Badge>
                ) : (
                    <Badge variant="neutral">—</Badge>
                ),
        },
        {
            accessorKey: 'duration_seconds',
            header: 'Duration',
            cell: ({ row }) => formatDuration(row.original.duration_seconds),
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/live/${row.original.id}`}>
                            {row.original.status === 'live' ? 'Studio' : 'Open'}
                        </Link>
                    </Button>
                    {row.original.material && (
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={`/materials/${row.original.material.id}`}>Recording</Link>
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Live Lessons"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Live Lessons' },
            ]}
        >
            <PageHeader
                title="Live Lessons"
                description="Broadcast a lesson to your students and keep the recording in the subject materials"
                actions={
                    <Button asChild>
                        <Link href="/live/create">
                            <Plus className="me-2 h-4 w-4" />
                            Start a session
                        </Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={sessions} emptyMessage="No live sessions yet." />
        </AppShell>
    );
}
