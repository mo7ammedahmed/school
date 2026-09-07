import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SchoolSettings({ school }: { school: { name: string; address?: string; phone?: string; email?: string; logo?: string; primary_color?: string } }) {
    const { data, setData, post, processing } = useForm({
        name: school.name || '',
        address: school.address || '',
        phone: school.phone || '',
        email: school.email || '',
        logo: school.logo || '',
        primary_color: school.primary_color || '#059669',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/school');
    };

    return (
        <AppShell
            title="School Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'School' },
            ]}
        >
            <PageHeader
                title="School Settings"
                description="Update school information and branding"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/general"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>School Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">School Name</Label>
                                <Input id="name" name="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" name="address" value={data.address} onChange={(e) => setData('address', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="logo">Logo URL</Label>
                                <Input id="logo" name="logo" value={data.logo} onChange={(e) => setData('logo', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="primary_color">Primary Color</Label>
                                <Input id="primary_color" name="primary_color" type="color" value={data.primary_color} onChange={(e) => setData('primary_color', e.target.value)} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/general">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Settings'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
