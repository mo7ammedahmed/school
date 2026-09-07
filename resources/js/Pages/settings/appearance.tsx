import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsAppearance() {
    return (
        <AppShell
            title="Appearance Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Appearance' },
            ]}
        >
            <PageHeader
                title="Appearance Settings"
                description="Customize the look and feel of your application"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Theme & Branding</CardTitle>
                        <CardDescription>Configure visual appearance and branding</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/appearance" method="POST" className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="app_theme">Application Theme</Label>
                                    <Input id="app_theme" defaultValue="light" />
                                </div>
                                <div>
                                    <Label htmlFor="primary_color">Primary Color</Label>
                                    <Input id="primary_color" type="color" defaultValue="#2563eb" className="h-10 w-full p-1" />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="logo_url">Logo URL</Label>
                                <Input id="logo_url" defaultValue="/images/logo.png" />
                            </div>
                            <div>
                                <Label htmlFor="favicon_url">Favicon URL</Label>
                                <Input id="favicon_url" defaultValue="/images/favicon.ico" />
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
                            <a href="/settings/localization">Localization</a>
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
