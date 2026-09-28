import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import { FormFeedback } from '@/components/ui/form-feedback';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Preferences({ preferences }: { preferences: { locale: string; timezone: string; theme: string; email_notifications: boolean; sms_notifications: boolean } }) {
    const { data, setData, post, processing, errors } = useForm({
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
                { label: 'Settings', href: '/settings/school' },
                { label: 'Preferences' },
            ]}
        >
            <PageHeader
                title="Preferences"
                description="Update your preferences"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/school"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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
                                <Select
                                    id="locale"
                                    value={data.locale}
                                    onChange={(e) => setData('locale', e.target.value)}
                                    error={errors.locale}
                                >
                                    <option value="en">English</option>
                                    <option value="ar">العربية</option>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="timezone">Timezone</Label>
                                <Input id="timezone" name="timezone" value={data.timezone} onChange={(e) => setData('timezone', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="theme">Theme</Label>
                                <Select
                                    id="theme"
                                    value={data.theme}
                                    onChange={(e) => setData('theme', e.target.value)}
                                    error={errors.theme}
                                >
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="system">System</option>
                                </Select>
                            </div>
                            <div className="flex items-center">
                                <Checkbox
                                    id="email_notifications"
                                    label="Email notifications"
                                    checked={data.email_notifications}
                                    onChange={(e) => setData('email_notifications', e.target.checked)}
                                />
                            </div>
                            <div className="flex items-center">
                                <Checkbox
                                    id="sms_notifications"
                                    label="SMS notifications"
                                    checked={data.sms_notifications}
                                    onChange={(e) => setData('sms_notifications', e.target.checked)}
                                />
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Preferences'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/school">Cancel</Link>
                            </Button>
                            <FormFeedback />
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
