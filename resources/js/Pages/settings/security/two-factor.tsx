import { useState, type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type TwoFactorSecurityProps = {
    twoFactorEnabled: boolean;
    secret?: string | null;
    provisioningUri?: string | null;
    recoveryCodesRemaining?: number;
};

export default function TwoFactorSecurity({
    twoFactorEnabled,
    secret,
    provisioningUri,
    recoveryCodesRemaining = 0,
}: TwoFactorSecurityProps) {
    const [copied, setCopied] = useState(false);

    const enableForm = useForm<{ code: string }>({ code: '' });
    const disableForm = useForm<{ password: string }>({ password: '' });

    const submitEnable = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        enableForm.post('/settings/security/two-factor/enable');
    };

    const submitDisable = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        disableForm.post('/settings/security/two-factor/disable');
    };

    const copySecret = async () => {
        if (!secret) return;

        try {
            await navigator.clipboard.writeText(secret);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard access can be denied; the secret stays visible to copy manually.
            setCopied(false);
        }
    };

    return (
        <AppShell
            title="Two-Factor Security"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Two-Factor Security' },
            ]}
        >
            <PageHeader
                title="Two-Factor Authentication"
                description="Manage your two-factor authentication settings"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/school">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Two-Factor Authentication</CardTitle>
                    <CardDescription>
                        Status: {twoFactorEnabled ? 'Enabled' : 'Disabled'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    {twoFactorEnabled ? (
                        <>
                            <p className="text-sm text-muted-foreground">
                                Recovery codes remaining: {recoveryCodesRemaining}
                            </p>

                            <form onSubmit={submitDisable} className="space-y-4 border-t border-border pt-5">
                                <div className="space-y-2">
                                    <Label htmlFor="disable-password">Confirm your password</Label>
                                    <Input
                                        id="disable-password"
                                        type="password"
                                        autoComplete="current-password"
                                        value={disableForm.data.password}
                                        onChange={(event) => disableForm.setData('password', event.target.value)}
                                    />
                                    {disableForm.errors.password && (
                                        <p className="text-sm text-destructive">{disableForm.errors.password}</p>
                                    )}
                                </div>

                                <Button type="submit" variant="destructive" disabled={disableForm.processing}>
                                    {disableForm.processing ? 'Disabling...' : 'Disable Two-Factor Authentication'}
                                </Button>
                            </form>
                        </>
                    ) : (
                        <>
                            <div className="space-y-3 rounded-lg border border-border bg-muted/40 p-4">
                                <p className="text-sm font-medium text-foreground">
                                    Add this secret to your authenticator app
                                </p>
                                <div className="flex flex-wrap items-center gap-3">
                                    <code className="min-w-0 flex-1 break-all font-mono text-sm text-foreground">
                                        {secret ?? 'Generating…'}
                                    </code>
                                    <Button type="button" variant="outline" size="sm" onClick={copySecret} disabled={!secret}>
                                        {copied ? 'Copied' : 'Copy'}
                                    </Button>
                                </div>
                                {provisioningUri && (
                                    <p className="break-all text-xs text-muted-foreground">{provisioningUri}</p>
                                )}
                            </div>

                            <form onSubmit={submitEnable} className="space-y-4 border-t border-border pt-5">
                                <div className="space-y-2">
                                    <Label htmlFor="enable-code">Enter the 6-digit code</Label>
                                    <Input
                                        id="enable-code"
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        maxLength={6}
                                        value={enableForm.data.code}
                                        onChange={(event) => enableForm.setData('code', event.target.value)}
                                    />
                                    {enableForm.errors.code && (
                                        <p className="text-sm text-destructive">{enableForm.errors.code}</p>
                                    )}
                                </div>

                                <Button type="submit" disabled={enableForm.processing || !secret}>
                                    {enableForm.processing ? 'Verifying...' : 'Enable Two-Factor Authentication'}
                                </Button>
                            </form>
                        </>
                    )}
                </CardContent>
            </Card>
        </AppShell>
    );
}
