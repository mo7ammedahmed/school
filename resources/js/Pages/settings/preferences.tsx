import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Preferences({ preferences }: { preferences: { locale: string; timezone: string; theme: string; email_notifications: boolean; sms_notifications: boolean } }) {
    const { data, setData, post, processing } = useForm({
        locale: preferences.locale || 'en',
        timezone: preferences.timezone || 'UTC',
        theme: preferences.theme || 'light',
        email_notifications: preferences.email_notifications ?? true,
        sms_notifications: preferences.sms_notifications ?? false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/preferences');
    };

    return (
        <AppShell
            title="Preferences"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Preferences' },
            ]}
        >
            <PageHeader
                title="Preferences"
                description="Update your preferences"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/general"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Preferences</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="locale">Language</Label>
                                <select id="locale" name="locale" className="input" value={data.locale} onChange={(e) => setData('locale', e.target.value)}>
                                    <option value="en">English</option>
                                    <option value="ar">Arabic</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="timezone">Timezone</Label>
                                <Input id="timezone" name="timezone" value={data.timezone} onChange={(e) => setData('timezone', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="theme">Theme</Label>
                                <select id="theme" name="theme" className="input" value={data.theme} onChange={(e) => setData('theme', e.target.value)}>
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="system">System</option>
                                </select>
                            </div>
                            <div className="flex items-center gap-2">
                                <input id="email_notifications" name="email_notifications" type="checkbox" checked={data.email_notifications} onChange={(e) => setData('email_notifications', e.target.checked)} />
                                <Label htmlFor="email_notifications">Email Notifications</Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <input id="sms_notifications" name="sms_notifications" type="checkbox" checked={data.sms_notifications} onChange={(e) => setData('sms_notifications', e.target.checked)} />
                                <Label htmlFor="sms_notifications">SMS Notifications</Label>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/general">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Preferences'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
