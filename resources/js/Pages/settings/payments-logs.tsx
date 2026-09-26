import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

type Transaction = {
    id: number;
    gateway: string;
    gateway_transaction_id: string;
    status: string;
    amount: string;
    created_at: string;
};

type WebhookEvent = {
    id: number;
    gateway: string;
    event_id: string;
    event_type: string | null;
    status: string;
    error_message: string | null;
    created_at: string;
};

type Props = {
    transactions: Transaction[];
    events: WebhookEvent[];
};

const statusVariant = (status: string) => {
    if (status === 'completed') return 'success' as const;
    if (status === 'failed') return 'destructive' as const;
    return 'secondary' as const;
};

export default function PaymentLogs({ transactions, events }: Props) {
    return (
        <AppShell
            title="Payment Logs"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Payments', href: '/settings/payments' },
                { label: 'Logs' },
            ]}
        >
            <PageHeader
                title="Payment Logs"
                description="Everything the gateway has reported for this school."
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/payments">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back to settings
                        </Link>
                    </Button>
                }
            />

            <div className="mt-6 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Webhook events</CardTitle>
                        <CardDescription>Raw notifications received from the provider.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {events.length === 0 ? (
                            <EmptyState
                                title="No webhook events yet"
                                description="Events appear here once the provider starts notifying us."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                            <th className="pb-2 pr-4">Gateway</th>
                                            <th className="pb-2 pr-4">Event</th>
                                            <th className="pb-2 pr-4">Type</th>
                                            <th className="pb-2 pr-4">Status</th>
                                            <th className="pb-2">Received</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {events.map((event) => (
                                            <tr key={event.id} className="border-t border-border/60">
                                                <td className="py-2 pr-4">{event.gateway}</td>
                                                <td className="py-2 pr-4 font-mono text-xs">{event.event_id}</td>
                                                <td className="py-2 pr-4">{event.event_type ?? '—'}</td>
                                                <td className="py-2 pr-4">
                                                    <Badge variant={statusVariant(event.status)}>
                                                        {event.status}
                                                    </Badge>
                                                    {event.error_message && (
                                                        <span className="ml-2 text-xs text-muted-foreground">
                                                            {event.error_message}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {new Date(event.created_at).toLocaleString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Gateway transactions</CardTitle>
                        <CardDescription>One row per payment attempt sent to the provider.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {transactions.length === 0 ? (
                            <EmptyState
                                title="No transactions yet"
                                description="Transactions appear here when a parent starts a card payment."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                            <th className="pb-2 pr-4">Gateway</th>
                                            <th className="pb-2 pr-4">Transaction</th>
                                            <th className="pb-2 pr-4">Amount</th>
                                            <th className="pb-2 pr-4">Status</th>
                                            <th className="pb-2">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {transactions.map((transaction) => (
                                            <tr key={transaction.id} className="border-t border-border/60">
                                                <td className="py-2 pr-4">{transaction.gateway}</td>
                                                <td className="py-2 pr-4 font-mono text-xs">
                                                    {transaction.gateway_transaction_id}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    {Number(transaction.amount).toFixed(2)}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    <Badge variant={statusVariant(transaction.status)}>
                                                        {transaction.status}
                                                    </Badge>
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {new Date(transaction.created_at).toLocaleString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
