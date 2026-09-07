import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link, router } from '@inertiajs/react';

export default function TwoFactorSecurity({ twoFactorEnabled, recoveryCodesRemaining }: { twoFactorEnabled: boolean; recoveryCodesRemaining?: number }) {
    return (
        <AppShell
            title="Two-Factor Security"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Two-Factor Security' },
            ]}
        >
            <PageHeader
                title="Two-Factor Authentication"
                description="Manage your two-factor authentication settings"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/general"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Two-Factor Authentication</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Status: {twoFactorEnabled ? 'Enabled' : 'Disabled'}
                            </p>
                            {twoFactorEnabled && recoveryCodesRemaining !== undefined && (
                                <p className="text-sm text-muted-foreground mt-1">
                                    Recovery codes remaining: {recoveryCodesRemaining}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-4">
                            {!twoFactorEnabled ? (
                                <Button type="button" onClick={() => router.post('/settings/security/two-factor/enable')}>
                                    Enable Two-Factor Authentication
                                </Button>
                            ) : (
                                <Button type="button" variant="destructive" onClick={() => router.post('/settings/security/two-factor/disable')}>
                                    Disable Two-Factor Authentication
                                </Button>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
