import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Password({ errors }: { errors?: Record<string, string> }) {
    const { data, setData, post, processing } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/password');
    };

    return (
        <AppShell
            title="Change Password"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Password' },
            ]}
        >
            <PageHeader
                title="Change Password"
                description="Update your password"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/general"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Password</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="current_password">Current Password</Label>
                                <Input id="current_password" name="current_password" type="password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} required />
                                {errors?.current_password && <p className="text-red-600 text-sm mt-1">{errors.current_password}</p>}
                            </div>
                            <div>
                                <Label htmlFor="password">New Password</Label>
                                <Input id="password" name="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                                {errors?.password && <p className="text-red-600 text-sm mt-1">{errors.password}</p>}
                            </div>
                            <div>
                                <Label htmlFor="password_confirmation">Confirm New Password</Label>
                                <Input id="password_confirmation" name="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} required />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/general">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Updating...' : 'Update Password'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
