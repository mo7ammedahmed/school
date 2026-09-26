import { type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { CreditCard, ExternalLink, ShieldCheck, AlertTriangle, History } from 'lucide-react';

type GatewayOption = { value: string; label: string; online: boolean };
type ChannelOption = { value: string; label: string };

type Settings = {
    gateway: string;
    mode: string;
    enabled: boolean;
    currency: string;
    auto_send: boolean;
    channels: string[];
    public_key: string | null;
    has_secret_key: boolean;
    has_webhook_secret: boolean;
    instructions: string | null;
};

type Activity = {
    transactions: number;
    completed: number;
    webhook_events: number;
    failed_webhooks: number;
};

type Props = {
    settings: Settings;
    gateways: GatewayOption[];
    channels: ChannelOption[];
    webhookUrl: string;
    activity: Activity;
    onlineCheckout: boolean;
};

const CURRENCIES = ['SAR', 'AED', 'USD', 'EGP', 'JOD', 'GBP', 'EUR'];

export default function SettingsPayments({
    settings,
    gateways,
    channels,
    webhookUrl,
    activity,
    onlineCheckout,
}: Props) {
    const form = useForm({
        gateway: settings.gateway,
        mode: settings.mode,
        enabled: settings.enabled,
        currency: settings.currency,
        auto_send: settings.auto_send,
        channels: settings.channels,
        public_key: settings.public_key ?? '',
        secret_key: '',
        webhook_secret: '',
        clear_secret_key: false,
        clear_webhook_secret: false,
        instructions: settings.instructions ?? '',
    });

    const selectedGateway = gateways.find((gateway) => gateway.value === form.data.gateway);
    const needsCredentials = selectedGateway?.online ?? false;

    const toggleChannel = (channel: string, checked: boolean) => {
        form.setData(
            'channels',
            checked
                ? [...form.data.channels, channel]
                : form.data.channels.filter((value) => value !== channel),
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/settings/payments', { preserveScroll: true });
    };

    return (
        <AppShell
            title="Payment Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Payment Settings' },
            ]}
        >
            <PageHeader
                title="Payment Settings"
                description="Choose how parents pay invoices, and what happens when you issue one."
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/payments/logs">
                            <History className="mr-2 h-4 w-4" />
                            Gateway logs
                        </Link>
                    </Button>
                }
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <form onSubmit={submit} className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="h-4 w-4" />
                                Payment gateway
                            </CardTitle>
                            <CardDescription>
                                Invoice payment links use this provider. Offline mode issues the invoice with
                                bank-transfer instructions instead.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="gateway">Provider</Label>
                                    <Select
                                        id="gateway"
                                        value={form.data.gateway}
                                        onChange={(event) => form.setData('gateway', event.target.value)}
                                    >
                                        {gateways.map((gateway) => (
                                            <option key={gateway.value} value={gateway.value}>
                                                {gateway.label}
                                            </option>
                                        ))}
                                    </Select>
                                    {form.errors.gateway && (
                                        <p className="mt-1 text-xs text-destructive">{form.errors.gateway}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select
                                        id="currency"
                                        value={form.data.currency}
                                        onChange={(event) => form.setData('currency', event.target.value)}
                                    >
                                        {CURRENCIES.map((currency) => (
                                            <option key={currency} value={currency}>
                                                {currency}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="mode">Mode</Label>
                                    <Select
                                        id="mode"
                                        value={form.data.mode}
                                        onChange={(event) => form.setData('mode', event.target.value)}
                                    >
                                        <option value="test">Test / sandbox</option>
                                        <option value="live">Live</option>
                                    </Select>
                                </div>

                                <div className="flex items-end">
                                    <label className="flex cursor-pointer items-start gap-3">
                                        <Checkbox
                                            checked={form.data.enabled}
                                            onChange={(event) => form.setData('enabled', event.target.checked)}
                                            className="mt-0.5"
                                        />
                                        <span className="text-sm">
                                            <span className="font-medium">Accept online card payments</span>
                                            <span className="block text-muted-foreground">
                                                Off when you only collect bank transfers.
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {needsCredentials && (
                                <div className="space-y-5 rounded-lg border border-border/60 bg-muted/30 p-4">
                                    <div>
                                        <Label htmlFor="public_key">Public / publishable key</Label>
                                        <Input
                                            id="public_key"
                                            value={form.data.public_key}
                                            onChange={(event) => form.setData('public_key', event.target.value)}
                                            placeholder={settings.public_key ?? 'pk_...'}
                                        />
                                    </div>

                                    <div className="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <Label htmlFor="secret_key">Secret key</Label>
                                            <Input
                                                id="secret_key"
                                                type="password"
                                                autoComplete="new-password"
                                                value={form.data.secret_key}
                                                onChange={(event) => form.setData('secret_key', event.target.value)}
                                                placeholder={settings.has_secret_key ? '•••••••• stored' : 'sk_...'}
                                            />
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Encrypted at rest. Leave blank to keep the stored key.
                                            </p>
                                            {settings.has_secret_key && (
                                                <label className="mt-2 flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                                                    <Checkbox
                                                        checked={form.data.clear_secret_key}
                                                        onChange={(event) =>
                                                            form.setData('clear_secret_key', event.target.checked)
                                                        }
                                                    />
                                                    Remove the stored key
                                                </label>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="webhook_secret">Webhook secret</Label>
                                            <Input
                                                id="webhook_secret"
                                                type="password"
                                                autoComplete="new-password"
                                                value={form.data.webhook_secret}
                                                onChange={(event) =>
                                                    form.setData('webhook_secret', event.target.value)
                                                }
                                                placeholder={
                                                    settings.has_webhook_secret ? '•••••••• stored' : 'whsec_...'
                                                }
                                            />
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Used to verify incoming payment notifications.
                                            </p>
                                            {settings.has_webhook_secret && (
                                                <label className="mt-2 flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                                                    <Checkbox
                                                        checked={form.data.clear_webhook_secret}
                                                        onChange={(event) =>
                                                            form.setData('clear_webhook_secret', event.target.checked)
                                                        }
                                                    />
                                                    Remove the stored secret
                                                </label>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="instructions">Offline payment instructions</Label>
                                <textarea
                                    id="instructions"
                                    rows={3}
                                    value={form.data.instructions}
                                    onChange={(event) => form.setData('instructions', event.target.value)}
                                    placeholder="Bank name, IBAN, account name — shown on the payment page when card payments are off."
                                    className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ShieldCheck className="h-4 w-4" />
                                Invoice delivery
                            </CardTitle>
                            <CardDescription>
                                When you issue an invoice, the payment link and PDF go to the student&apos;s
                                guardians automatically.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <label className="flex cursor-pointer items-start gap-3">
                                <Checkbox
                                    checked={form.data.auto_send}
                                    onChange={(event) => form.setData('auto_send', event.target.checked)}
                                    className="mt-0.5"
                                />
                                <span className="text-sm">
                                    <span className="font-medium">Send invoices automatically on issue</span>
                                    <span className="block text-muted-foreground">
                                        Turn this off to issue quietly and send manually from the invoice page.
                                    </span>
                                </span>
                            </label>

                            <div>
                                <Label>Channels</Label>
                                <div className="mt-2 space-y-2">
                                    {channels.map((channel) => (
                                        <label
                                            key={channel.value}
                                            className="flex cursor-pointer items-center gap-3 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.channels.includes(channel.value)}
                                                onChange={(event) =>
                                                    toggleChannel(channel.value, event.target.checked)
                                                }
                                            />
                                            {channel.label}
                                        </label>
                                    ))}
                                </div>
                                {form.data.channels.includes('sms') && (
                                    <p className="mt-2 flex items-start gap-2 text-xs text-muted-foreground">
                                        <AlertTriangle className="mt-0.5 h-3.5 w-3.5" />
                                        SMS needs a Unifonic or Twilio account in{' '}
                                        <Link href="/settings/sms" className="underline">
                                            SMS settings
                                        </Link>
                                        , otherwise messages are only written to the log.
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </Button>
                        {form.recentlySuccessful && (
                            <span className="text-sm text-muted-foreground">Saved.</span>
                        )}
                    </div>
                </form>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Gateway</span>
                                <span className="font-medium">{selectedGateway?.label ?? settings.gateway}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Online checkout</span>
                                <Badge variant={onlineCheckout ? 'success' : 'secondary'}>
                                    {onlineCheckout ? 'available' : 'not configured'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Secret key</span>
                                <Badge variant={settings.has_secret_key ? 'success' : 'secondary'}>
                                    {settings.has_secret_key ? 'stored' : 'missing'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Webhook secret</span>
                                <Badge variant={settings.has_webhook_secret ? 'success' : 'secondary'}>
                                    {settings.has_webhook_secret ? 'stored' : 'missing'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Webhook endpoint</CardTitle>
                            <CardDescription>Paste this into your provider dashboard.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <code className="block break-all rounded-md bg-muted p-2 text-xs">{webhookUrl}</code>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent activity</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Transactions</span>
                                <span className="font-medium">{activity.transactions}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Completed</span>
                                <span className="font-medium">{activity.completed}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Webhook events</span>
                                <span className="font-medium">{activity.webhook_events}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Failed webhooks</span>
                                <span className="font-medium">{activity.failed_webhooks}</span>
                            </div>
                            <Button variant="outline" className="mt-2 w-full justify-start" asChild>
                                <Link href="/settings/payments/logs">
                                    <ExternalLink className="mr-2 h-4 w-4" />
                                    View logs
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppShell>
    );
}
