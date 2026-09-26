import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type SchoolSettings = {
    id: number;
    name: string;
    name_en: string | null;
    name_ar: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    logo_path: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    accent_color: string | null;
};

export default function SchoolSettings({ school }: { school: SchoolSettings }) {
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
                    <form
                        className="space-y-6"
                        method="POST"
                        action="/settings/school"
                        encType="multipart/form-data"
                    >
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_en">School Name (English)</Label>
                                <Input id="name_en" name="name_en" defaultValue={school.name_en ?? ''} required />
                                <TranslatePair enId="name_en" arId="name_ar" />
                            </div>
                            <div>
                                <Label htmlFor="name_ar">School Name (Arabic)</Label>
                                <Input id="name_ar" name="name_ar" dir="rtl" defaultValue={school.name_ar ?? ''} />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={school.email ?? ''} required />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" defaultValue={school.phone ?? ''} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" name="address" defaultValue={school.address ?? ''} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="logo">Logo</Label>
                                <Input id="logo" name="logo" type="file" accept="image/*" />
                                {school.logo_path && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        A logo is already stored. Upload a new file to replace it.
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-3">
                            <div>
                                <Label htmlFor="primary_color">Primary Color</Label>
                                <Input id="primary_color" name="primary_color" type="color" defaultValue={school.primary_color ?? '#0a5c42'} />
                            </div>
                            <div>
                                <Label htmlFor="secondary_color">Secondary Color</Label>
                                <Input id="secondary_color" name="secondary_color" type="color" defaultValue={school.secondary_color ?? '#f2efe8'} />
                            </div>
                            <div>
                                <Label htmlFor="accent_color">Accent Color</Label>
                                <Input id="accent_color" name="accent_color" type="color" defaultValue={school.accent_color ?? '#efecdf'} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/general">Cancel</Link>
                            </Button>
                            <Button type="submit">Save Settings</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
