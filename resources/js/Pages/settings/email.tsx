import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsEmail() {
    return (
        <AppShell
            title="Email Configuration"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Email Configuration' },
            ]}
        >
            <PageHeader
                title="Email Configuration"
                description="Configure SMTP settings for sending emails"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>SMTP Settings</CardTitle>
                        <CardDescription>Configure your SMTP server for sending emails</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/email" method="POST" className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="mail_driver">Mail Driver</Label>
                                    <Input id="mail_driver" defaultValue="smtp" />
                                </div>
                                <div>
                                    <Label htmlFor="mail_host">SMTP Host</Label>
                                    <Input id="mail_host" defaultValue="smtp.gmail.com" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="mail_port">SMTP Port</Label>
                                    <Input id="mail_port" type="number" defaultValue="587" />
                                </div>
                                <div>
                                    <Label htmlFor="mail_encryption">Encryption</Label>
                                    <Input id="mail_encryption" defaultValue="tls" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="mail_username">Username</Label>
                                    <Input id="mail_username" defaultValue="noreply@school.edu" />
                                </div>
                                <div>
                                    <Label htmlFor="mail_password">Password</Label>
                                    <Input id="mail_password" type="password" />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="mail_from_address">From Address</Label>
                                <Input id="mail_from_address" type="email" defaultValue="noreply@school.edu" />
                            </div>
                            <div>
                                <Label htmlFor="mail_from_name">From Name</Label>
                                <Input id="mail_from_name" defaultValue="School Administration" />
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
