import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function TwoFactorSecurity({ enabled, recoveryCodesRemaining }: { enabled: boolean; recoveryCodesRemaining?: number }) {
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
                                Status: {enabled ? 'Enabled' : 'Disabled'}
                            </p>
                            {enabled && recoveryCodesRemaining !== undefined && (
                                <p className="text-sm text-muted-foreground mt-1">
                                    Recovery codes remaining: {recoveryCodesRemaining}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-4">
                            {!enabled ? (
                                <Button asChild>
                                    <Link href="/settings/security/two-factor/enable">Enable Two-Factor Authentication</Link>
                                </Button>
                            ) : (
                                <Button variant="destructive" asChild>
                                    <Link href="/settings/security/two-factor/disable">Disable Two-Factor Authentication</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
