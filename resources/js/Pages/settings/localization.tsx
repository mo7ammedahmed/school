import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsLocalization() {
    return (
        <AppShell
            title="Localization Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Localization' },
            ]}
        >
            <PageHeader
                title="Localization Settings"
                description="Configure language, date format, currency, and number formats"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Language & Region</CardTitle>
                        <CardDescription>Set default language and regional preferences</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/localization" method="POST" className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="default_locale">Default Language</Label>
                                    <Input id="default_locale" defaultValue="en" />
                                </div>
                                <div>
                                    <Label htmlFor="default_timezone">Default Timezone</Label>
                                    <Input id="default_timezone" defaultValue="Asia/Riyadh" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="date_format">Date Format</Label>
                                    <Input id="date_format" defaultValue="Y-m-d" />
                                </div>
                                <div>
                                    <Label htmlFor="time_format">Time Format</Label>
                                    <Input id="time_format" defaultValue="H:i" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="currency">Currency</Label>
                                    <Input id="currency" defaultValue="SAR" />
                                </div>
                                <div>
                                    <Label htmlFor="currency_symbol">Currency Symbol</Label>
                                    <Input id="currency_symbol" defaultValue="SAR" />
                                </div>
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="number_format">Number Format</Label>
                                    <Input id="number_format" defaultValue="1,234.56" />
                                </div>
                                <div>
                                    <Label htmlFor="week_start">Week Starts On</Label>
                                    <Input id="week_start" type="number" defaultValue="0" />
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
