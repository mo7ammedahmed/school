import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsPayments() {
    return (
        <AppShell
            title="Payment Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Payments' },
            ]}
        >
            <PageHeader
                title="Payment Settings"
                description="Configure payment gateway settings"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Payment Gateway</CardTitle>
                        <CardDescription>Configure your payment provider credentials</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/payments" method="POST" className="space-y-6">
                            <div>
                                <Label htmlFor="payment_gateway">Payment Gateway</Label>
                                <Input id="payment_gateway" defaultValue="stripe" />
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="payment_api_key">API Key</Label>
                                    <Input id="payment_api_key" type="password" />
                                </div>
                                <div>
                                    <Label htmlFor="payment_api_secret">API Secret</Label>
                                    <Input id="payment_api_secret" type="password" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="payment_webhook_url">Webhook URL</Label>
                                    <Input id="payment_webhook_url" type="url" defaultValue="https://school.edu/webhooks/payment" />
                                </div>
                                <div>
                                    <Label htmlFor="payment_currency">Default Currency</Label>
                                    <Input id="payment_currency" defaultValue="SAR" />
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <Button type="button" variant="outline">Cancel</Button>
                                <Button type="submit">Save Changes</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/general">General Settings</a>
                        </Button>
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/notifications-config">Notifications</a>
                        </Button>
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/email">Email Configuration</a>
                        </Button>
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/security">Security Settings</a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
