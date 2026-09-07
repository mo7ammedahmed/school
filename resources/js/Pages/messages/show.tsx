import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface Message {
    id: number;
    body: string;
    created_at: string;
    sender: { id: number; name: string } | null;
}

export default function MessagesShow({
    conversation,
}: {
    conversation: { id: number; subject: string; type: string; messages: Message[] };
}) {
    return (
        <AppShell
            title={conversation.subject}
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Messages', href: '/messages' },
                { label: conversation.subject },
            ]}
        >
            <PageHeader
                title={conversation.subject}
                description={`${conversation.messages.length} message${conversation.messages.length === 1 ? '' : 's'}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/messages"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Thread</CardTitle>
                </CardHeader>
                <CardContent>
                    {conversation.messages.length === 0 ? (
                        <p className="py-8 text-center text-sm text-muted-foreground">No messages in this conversation yet.</p>
                    ) : (
                        <div className="space-y-4">
                            {conversation.messages.map((message) => (
                                <div key={message.id} className="rounded-xl border border-border/70 bg-muted/40 p-4">
                                    <div className="mb-1 flex items-center justify-between gap-3">
                                        <p className="text-sm font-semibold text-foreground">{message.sender?.name || 'Unknown'}</p>
                                        <p className="text-xs text-muted-foreground">{message.created_at}</p>
                                    </div>
                                    <p className="whitespace-pre-wrap text-sm leading-relaxed text-foreground/90">{message.body}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppShell>
    );
}
