import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { FormFeedback } from '@/components/ui/form-feedback';

type MailSettings = {
    mail_driver?: string;
    mail_host?: string;
    mail_port?: number | string;
    mail_encryption?: string;
    mail_username?: string;
    mail_from_address?: string;
    mail_from_name?: string;
    has_mail_password?: boolean;
};

export default function SettingsEmail({ settings = {} }: { settings?: MailSettings }) {
    return (
        <AppShell
            title="Email Configuration"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
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
                                    <Input id="mail_driver" name="mail_driver" defaultValue={settings.mail_driver ?? 'smtp'} />
                                </div>
                                <div>
                                    <Label htmlFor="mail_host">SMTP Host</Label>
                                    <Input id="mail_host" name="mail_host" defaultValue={settings.mail_host ?? 'smtp.gmail.com'} />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="mail_port">SMTP Port</Label>
                                    <Input id="mail_port" name="mail_port" type="number" defaultValue={String(settings.mail_port ?? 587)} />
                                </div>
                                <div>
                                    <Label htmlFor="mail_encryption">Encryption</Label>
                                    <Input id="mail_encryption" name="mail_encryption" defaultValue={settings.mail_encryption ?? 'tls'} />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="mail_username">Username</Label>
                                    <Input id="mail_username" name="mail_username" defaultValue={settings.mail_username ?? ''} />
                                </div>
                                <div>
                                    <Label htmlFor="mail_password">Password</Label>
                                    <Input
                                        id="mail_password"
                                        name="mail_password"
                                        type="password"
                                        placeholder={settings.has_mail_password ? '•••••••• stored' : ''}
                                    />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="mail_from_address">From Address</Label>
                                <Input id="mail_from_address" name="mail_from_address" type="email" defaultValue={settings.mail_from_address ?? 'noreply@school.edu'} />
                            </div>
                            <div>
                                <Label htmlFor="mail_from_name">From Name</Label>
                                <Input id="mail_from_name" name="mail_from_name" defaultValue={settings.mail_from_name ?? 'School Administration'} />
                            </div>
                            <div className="flex flex-wrap items-center gap-4">
                                <Button type="submit">Save Changes</Button>
                                <Button type="button" variant="outline" asChild>
                                    <a href="/settings/school">Cancel</a>
                                </Button>
                                <FormFeedback showErrors={false} />
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
                            <a href="/settings/school">General Settings</a>
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
