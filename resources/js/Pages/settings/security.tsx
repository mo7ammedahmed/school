import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsSecurity() {
    return (
        <AppShell
            title="Security Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Security' },
            ]}
        >
            <PageHeader
                title="Security Settings"
                description="Configure password policies, session timeouts, and IP restrictions"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Password Policy</CardTitle>
                        <CardDescription>Set password requirements for all users</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/security" method="POST" className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="password_min_length">Minimum Password Length</Label>
                                    <Input id="password_min_length" type="number" defaultValue="8" />
                                </div>
                                <div>
                                    <Label htmlFor="password_expiry_days">Password Expiry (Days)</Label>
                                    <Input id="password_expiry_days" type="number" defaultValue="90" />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="password_require_uppercase">Require Uppercase</Label>
                                    <p className="text-sm text-muted-foreground">Password must contain at least one uppercase letter</p>
                                </div>
                                <Input id="password_require_uppercase" type="checkbox" defaultChecked className="h-5 w-5" />
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="password_require_numbers">Require Numbers</Label>
                                    <p className="text-sm text-muted-foreground">Password must contain at least one number</p>
                                </div>
                                <Input id="password_require_numbers" type="checkbox" defaultChecked className="h-5 w-5" />
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="password_require_symbols">Require Special Characters</Label>
                                    <p className="text-sm text-muted-foreground">Password must contain at least one special character</p>
                                </div>
                                <Input id="password_require_symbols" type="checkbox" defaultChecked className="h-5 w-5" />
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

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Session & IP Settings</CardTitle>
                        <CardDescription>Manage session timeouts and IP restrictions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/security" method="POST" className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="session_timeout">Session Timeout (Minutes)</Label>
                                    <Input id="session_timeout" type="number" defaultValue="60" />
                                </div>
                                <div>
                                    <Label htmlFor="max_login_attempts">Max Login Attempts</Label>
                                    <Input id="max_login_attempts" type="number" defaultValue="5" />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="allowed_ips">Allowed IP Addresses (comma-separated)</Label>
                                <Input id="allowed_ips" defaultValue="192.168.1.0/24, 10.0.0.0/8" />
                            </div>
                            <div className="flex gap-4">
                                <Button type="button" variant="outline">Cancel</Button>
                                <Button type="submit">Save Changes</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
