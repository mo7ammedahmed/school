import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { FormFeedback } from '@/components/ui/form-feedback';
import { Card, CardContent, CardTitle, CardDescription, CardHeader } from '@/components/ui/card';

type SecuritySettings = {
    password_min_length?: number | string;
    password_expiry_days?: number | string;
    password_require_uppercase?: boolean;
    password_require_numbers?: boolean;
    password_require_symbols?: boolean;
    session_timeout?: number | string;
    max_login_attempts?: number | string;
    allowed_ips?: string;
};

export default function SettingsSecurity({ settings = {} }: { settings?: SecuritySettings }) {
    return (
        <AppShell
            title="Security Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Security' },
            ]}
        >
            <PageHeader
                title="Security Settings"
                description="Configure password policies, session timeouts, and IP restrictions"
            />

            <form action="/settings/security" method="POST" className="mt-6 space-y-6">
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Password Policy</CardTitle>
                            <CardDescription>Set password requirements for all users</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="password_min_length">Minimum Password Length</Label>
                                    <Input
                                        id="password_min_length"
                                        name="password_min_length"
                                        type="number"
                                        min={6}
                                        max={128}
                                        defaultValue={String(settings.password_min_length ?? 8)}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="password_expiry_days">Password Expiry (Days)</Label>
                                    <Input
                                        id="password_expiry_days"
                                        name="password_expiry_days"
                                        type="number"
                                        min={0}
                                        max={730}
                                        defaultValue={String(settings.password_expiry_days ?? 90)}
                                    />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="password_require_uppercase">Require uppercase</Label>
                                    <p className="text-sm text-muted-foreground">Password must contain at least one uppercase letter</p>
                                </div>
                                <Checkbox
                                    id="password_require_uppercase"
                                    name="password_require_uppercase"
                                    value="1"
                                    defaultChecked={settings.password_require_uppercase ?? true}
                                    className="shrink-0"
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="password_require_numbers">Require numbers</Label>
                                    <p className="text-sm text-muted-foreground">Password must contain at least one number</p>
                                </div>
                                <Checkbox
                                    id="password_require_numbers"
                                    name="password_require_numbers"
                                    value="1"
                                    defaultChecked={settings.password_require_numbers ?? true}
                                    className="shrink-0"
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="password_require_symbols">Require special characters</Label>
                                    <p className="text-sm text-muted-foreground">Password must contain at least one special character</p>
                                </div>
                                <Checkbox
                                    id="password_require_symbols"
                                    name="password_require_symbols"
                                    value="1"
                                    defaultChecked={settings.password_require_symbols ?? true}
                                    className="shrink-0"
                                />
                            </div>
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

                <Card>
                    <CardHeader>
                        <CardTitle>Session & IP Settings</CardTitle>
                        <CardDescription>Manage session timeouts and IP restrictions</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="session_timeout">Session Timeout (Minutes)</Label>
                                <Input
                                    id="session_timeout"
                                    name="session_timeout"
                                    type="number"
                                    min={5}
                                    defaultValue={String(settings.session_timeout ?? 60)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="max_login_attempts">Max Login Attempts</Label>
                                <Input
                                    id="max_login_attempts"
                                    name="max_login_attempts"
                                    type="number"
                                    min={1}
                                    defaultValue={String(settings.max_login_attempts ?? 5)}
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="allowed_ips">Allowed IP Addresses (comma-separated)</Label>
                            <Input
                                id="allowed_ips"
                                name="allowed_ips"
                                defaultValue={settings.allowed_ips ?? ''}
                                placeholder="192.168.1.0/24, 10.0.0.0/8"
                            />
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-wrap items-center gap-4">
                    <Button type="submit">Save Changes</Button>
                    <Button type="button" variant="outline" asChild>
                        <a href="/settings/school">Cancel</a>
                    </Button>
                    <FormFeedback showErrors={false} />
                </div>
            </form>
        </AppShell>
    );
}
