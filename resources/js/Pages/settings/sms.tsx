// Facts for this file:
// 1. Called by: routes/web.php (GET and POST /settings/sms).
// 2. Data flow: reads `settings` (masked), `providers` and `configured` from
//    SmsSettingsController; posts the exact field names the controller
//    validates (`provider`, `sender_id`, `account_sid`, `api_key`,
//    `auth_token`). Blank secrets keep the stored value.
// 3. This page used to ignore its props, name its fields `sms_*` and post
//    nothing, so every save failed validation silently.

import { type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormFeedback } from '@/components/ui/form-feedback';
import { MessageSquare, ShieldCheck, AlertTriangle } from 'lucide-react';

type SmsSettings = {
    provider: string;
    sender_id: string | null;
    account_sid: string | null;
    has_api_key: boolean;
    has_auth_token: boolean;
};

type Props = {
    settings: SmsSettings;
    providers: { value: string; label: string }[];
    configured: boolean;
};

const SECRET_PLACEHOLDER = '•••••••• stored — leave blank to keep';

export default function SettingsSms({ settings, providers, configured }: Props) {
    const form = useForm({
        provider: settings.provider,
        sender_id: settings.sender_id ?? '',
        account_sid: settings.account_sid ?? '',
        api_key: '',
        auth_token: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/settings/sms', { preserveScroll: true });
    };

    const providerLabel = providers.find((provider) => provider.value === form.data.provider)?.label ?? form.data.provider;

    return (
        <AppShell
            title="SMS Configuration"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'SMS Configuration' },
            ]}
        >
            <PageHeader
                title="SMS Configuration"
                description="Choose the gateway used to text guardians, and store its credentials."
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <MessageSquare className="h-4 w-4" aria-hidden="true" />
                            SMS gateway
                        </CardTitle>
                        <CardDescription>
                            Currently set to <span className="font-medium text-foreground">{providerLabel}</span>.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <Label htmlFor="provider">Provider</Label>
                                <Select
                                    id="provider"
                                    value={form.data.provider}
                                    onChange={(event) => form.setData('provider', event.target.value)}
                                >
                                    {providers.map((provider) => (
                                        <option key={provider.value} value={provider.value}>
                                            {provider.label}
                                        </option>
                                    ))}
                                </Select>
                                {form.errors.provider && (
                                    <p className="mt-1.5 text-xs text-destructive">{form.errors.provider}</p>
                                )}
                                <p className="mt-1.5 text-xs text-muted-foreground">
                                    The log provider records messages instead of sending them, so nothing leaves the server.
                                </p>
                            </div>

                            {form.data.provider !== 'log' && (
                                <div className="space-y-5 rounded-lg border border-border/60 bg-muted/30 p-4">
                                    <div>
                                        <Label htmlFor="sender_id">Sender ID</Label>
                                        <Input
                                            id="sender_id"
                                            value={form.data.sender_id}
                                            onChange={(event) => form.setData('sender_id', event.target.value)}
                                            error={form.errors.sender_id}
                                            placeholder="SchoolOS"
                                        />
                                        <p className="mt-1.5 text-xs text-muted-foreground">
                                            The name or number recipients see. Approved alphanumeric sender IDs are required in
                                            Saudi Arabia.
                                        </p>
                                    </div>

                                    {form.data.provider === 'twilio' && (
                                        <>
                                            <div>
                                                <Label htmlFor="account_sid">Account SID</Label>
                                                <Input
                                                    id="account_sid"
                                                    value={form.data.account_sid}
                                                    onChange={(event) => form.setData('account_sid', event.target.value)}
                                                    error={form.errors.account_sid}
                                                    placeholder="AC…"
                                                />
                                            </div>
                                            <div>
                                                <Label htmlFor="auth_token">Auth token</Label>
                                                <Input
                                                    id="auth_token"
                                                    type="password"
                                                    autoComplete="new-password"
                                                    value={form.data.auth_token}
                                                    onChange={(event) => form.setData('auth_token', event.target.value)}
                                                    error={form.errors.auth_token}
                                                    placeholder={settings.has_auth_token ? SECRET_PLACEHOLDER : ''}
                                                />
                                            </div>
                                        </>
                                    )}

                                    {form.data.provider === 'unifonic' && (
                                        <div>
                                            <Label htmlFor="api_key">API key</Label>
                                            <Input
                                                id="api_key"
                                                type="password"
                                                autoComplete="new-password"
                                                value={form.data.api_key}
                                                onChange={(event) => form.setData('api_key', event.target.value)}
                                                error={form.errors.api_key}
                                                placeholder={settings.has_api_key ? SECRET_PLACEHOLDER : ''}
                                            />
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Saving…' : 'Save SMS settings'}
                                </Button>
                                <Button type="button" variant="outline" onClick={() => form.reset()} disabled={form.processing}>
                                    Reset changes
                                </Button>
                                <FormFeedback />
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {configured ? (
                            <div className="flex items-start gap-3">
                                <ShieldCheck className="mt-0.5 h-4 w-4 text-success" aria-hidden="true" />
                                <div>
                                    <Badge>Ready</Badge>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Credentials are stored and messages will be sent with {providerLabel}.
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-start gap-3">
                                <AlertTriangle className="mt-0.5 h-4 w-4 text-warning" aria-hidden="true" />
                                <div>
                                    <Badge variant="destructive">Not sending</Badge>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Messages are only written to the log. Add the credentials for{' '}
                                        {providerLabel} to start delivering them.
                                    </p>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
