import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

interface LastMessage {
    body: string;
    created_at: string;
    sender: { name: string };
}

export default function MessagesIndex({
    conversations,
}: {
    conversations: { id: number; subject: string; type: string; messages_count: number; last_message: LastMessage | null }[];
}) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'subject',
            header: 'Conversation',
            cell: ({ row }) => (
                <div className="max-w-[24rem]">
                    <p className="truncate font-medium text-foreground">{row.original.subject}</p>
                    <p className="truncate text-xs text-muted-foreground">
                        {row.original.last_message?.sender.name}: {row.original.last_message?.body}
                    </p>
                </div>
            ),
        },
        {
            accessorKey: 'type',
            header: 'Type',
            cell: ({ row }) => <Badge variant="secondary" className="capitalize">{row.original.type}</Badge>,
        },
        {
            accessorKey: 'messages_count',
            header: 'Messages',
            cell: ({ row }) => row.original.messages_count,
        },
        {
            accessorKey: 'last_message.created_at',
            header: 'Last Activity',
            cell: ({ row }) => row.original.last_message?.created_at || '—',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <Button variant="ghost" size="sm" asChild>
                    <Link href={`/messages/${row.original.id}`}>Open</Link>
                </Button>
            ),
        },
    ];

    return (
        <AppShell
            title="Messages"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Messages' },
            ]}
        >
            <PageHeader
                title="Messages"
                description="School conversations and announcements"
                actions={
                    <Button asChild>
                        <Link href="/messages/create"><Plus className="mr-2 h-4 w-4" />New Message</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={conversations} />
        </AppShell>
    );
}
