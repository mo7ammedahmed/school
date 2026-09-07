import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsSms() {
    return (
        <AppShell
            title="SMS Configuration"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'SMS Configuration' },
            ]}
        >
            <PageHeader
                title="SMS Configuration"
                description="Configure SMS gateway settings"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>SMS Gateway</CardTitle>
                        <CardDescription>Configure your SMS provider credentials</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/sms" method="POST" className="space-y-6">
                            <div>
                                <Label htmlFor="sms_gateway">SMS Gateway Provider</Label>
                                <Input id="sms_gateway" defaultValue="twilio" />
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="sms_api_key">API Key</Label>
                                    <Input id="sms_api_key" type="password" />
                                </div>
                                <div>
                                    <Label htmlFor="sms_api_secret">API Secret</Label>
                                    <Input id="sms_api_secret" type="password" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="sms_sender_id">Sender ID</Label>
                                    <Input id="sms_sender_id" defaultValue="SchoolOS" />
                                </div>
                                <div>
                                    <Label htmlFor="sms_country_code">Default Country Code</Label>
                                    <Input id="sms_country_code" defaultValue="+966" />
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
                            <a href="/settings/sms">SMS Configuration</a>
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
