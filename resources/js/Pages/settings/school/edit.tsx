// Facts for this file:
// 1. Called by: routes/web.php (GET /settings/school and POST /settings/school).
//    /settings/general forwards here, so this is the only school editor — the
//    old settings/general.tsx and settings/school.tsx duplicates are gone.
// 2. Data flow: reads the `school` prop, posts one multipart form (bilingual
//    name + description, contact details, logo upload, brand colours).
// 3. Brand colours are also written by Settings → Appearance (the light accent
//    becomes primary_color), so the hint below says so out loud instead of
//    letting the two screens silently fight over one column.

import { type FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ColorField } from '@/components/ui/color-field';
import { FormFeedback } from '@/components/ui/form-feedback';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Image as ImageIcon, Palette } from 'lucide-react';

type SchoolSettings = {
    id: number;
    name: string;
    name_en: string | null;
    name_ar: string | null;
    description_en: string | null;
    description_ar: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    logo_path: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    accent_color: string | null;
};

export default function SchoolSettings({ school }: { school: SchoolSettings }) {
    const form = useForm({
        name_en: school.name_en ?? school.name ?? '',
        name_ar: school.name_ar ?? '',
        description_en: school.description_en ?? '',
        description_ar: school.description_ar ?? '',
        email: school.email ?? '',
        phone: school.phone ?? '',
        address: school.address ?? '',
        logo: null as File | null,
        primary_color: school.primary_color ?? '#0a5c42',
        secondary_color: school.secondary_color ?? '#f2efe8',
        accent_color: school.accent_color ?? '#efecdf',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/settings/school', { forceFormData: true, preserveScroll: true });
    };

    const colours: Array<{ key: 'primary_color' | 'secondary_color' | 'accent_color'; label: string; hint?: string }> = [
        {
            key: 'primary_color',
            label: 'Primary colour',
            hint: 'Also edited on Settings → Appearance, where the light palette accent becomes this value.',
        },
        { key: 'secondary_color', label: 'Secondary colour' },
        { key: 'accent_color', label: 'Accent colour' },
    ];

    return (
        <AppShell
            title="School Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings' },
                { label: 'School' },
            ]}
        >
            <PageHeader
                title="School settings"
                description="Your school's identity, contact details and brand colours."
            />

            <form onSubmit={submit} className="mt-6 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Identity</CardTitle>
                        <CardDescription>
                            The English and Arabic names are both shown around the app and on the public website.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_en">School name (English)</Label>
                                <Input
                                    id="name_en"
                                    value={form.data.name_en}
                                    onChange={(event) => form.setData('name_en', event.target.value)}
                                    error={form.errors.name_en}
                                />
                                <TranslatePair enId="name_en" arId="name_ar" persist={{ table: 'schools', id: school.id, enColumn: 'name_en', arColumn: 'name_ar' }} />
                            </div>
                            <div>
                                <Label htmlFor="name_ar">School name (Arabic)</Label>
                                <Input
                                    id="name_ar"
                                    dir="rtl"
                                    value={form.data.name_ar}
                                    onChange={(event) => form.setData('name_ar', event.target.value)}
                                    error={form.errors.name_ar}
                                />
                            </div>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <Label htmlFor="description_en">Description (English)</Label>
                                <Textarea
                                    id="description_en"
                                    value={form.data.description_en}
                                    onChange={(event) => form.setData('description_en', event.target.value)}
                                    error={form.errors.description_en}
                                />
                                <TranslatePair enId="description_en" arId="description_ar" persist={{ table: 'schools', id: school.id, enColumn: 'description_en', arColumn: 'description_ar' }} />
                            </div>
                            <div>
                                <Label htmlFor="description_ar">Description (Arabic)</Label>
                                <Textarea
                                    id="description_ar"
                                    dir="rtl"
                                    value={form.data.description_ar}
                                    onChange={(event) => form.setData('description_ar', event.target.value)}
                                    error={form.errors.description_ar}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Contact</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 md:grid-cols-2">
                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                error={form.errors.email}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="phone">Phone</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(event) => form.setData('phone', event.target.value)}
                                error={form.errors.phone}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Label htmlFor="address">Address</Label>
                            <Input
                                id="address"
                                value={form.data.address}
                                onChange={(event) => form.setData('address', event.target.value)}
                                error={form.errors.address}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ImageIcon className="h-4 w-4" aria-hidden="true" />
                            Logo
                        </CardTitle>
                        <CardDescription>
                            Shown in the sidebar, on PDF exports and on the public website.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {school.logo_path && (
                            <div className="flex items-center gap-3">
                                <img
                                    src={`/storage/${school.logo_path}`}
                                    alt="Current school logo"
                                    className="h-14 w-14 rounded-lg border border-border bg-card object-contain p-1"
                                />
                                <p className="text-xs text-muted-foreground">
                                    A logo is already stored. Choose a new file to replace it.
                                </p>
                            </div>
                        )}
                        <div>
                            <Label htmlFor="logo">Logo file</Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                onChange={(event) => form.setData('logo', event.target.files?.[0] ?? null)}
                                error={form.errors.logo}
                                hint="PNG, JPG or WebP up to 2 MB."
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Palette className="h-4 w-4" aria-hidden="true" />
                            Brand colours
                        </CardTitle>
                        <CardDescription>
                            Every other colour, gradient and website token lives on the Appearance screen.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-5 md:grid-cols-3">
                            {colours.map((colour) => (
                                <ColorField
                                    key={colour.key}
                                    label={colour.label}
                                    value={form.data[colour.key]}
                                    onChange={(value) => form.setData(colour.key, value)}
                                    error={form.errors[colour.key]}
                                    hint={colour.hint}
                                />
                            ))}
                        </div>
                        <p className="mt-5 text-sm text-muted-foreground">
                            Need gradients, dark-mode palettes or the website colours?{' '}
                            <Link href="/settings/appearance" className="font-medium text-primary underline-offset-4 hover:underline">
                                Open Appearance &amp; Theme
                            </Link>
                            .
                        </p>
                    </CardContent>
                </Card>

                <div className="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Saving…' : 'Save school settings'}
                    </Button>
                    <Button type="button" variant="outline" onClick={() => form.reset()} disabled={form.processing}>
                        Reset changes
                    </Button>
                    <FormFeedback />
                </div>
            </form>
        </AppShell>
    );
}
