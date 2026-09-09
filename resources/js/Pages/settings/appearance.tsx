import { useEffect, useState, type ChangeEvent, type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Appearance = {
    theme: 'light' | 'dark' | 'system';
    primary_color: string;
    secondary_color: string;
    logo_path?: string | null;
    favicon_path?: string | null;
};

type AppearanceProps = {
    appearance: Appearance;
};

export default function AppearanceSettings({ appearance }: AppearanceProps) {
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const { data, setData, post, processing, errors, recentlySuccessful, reset } = useForm<{
        theme: Appearance['theme'];
        primary_color: string;
        secondary_color: string;
        logo: File | null;
        favicon: File | null;
    }>({
        theme: appearance.theme,
        primary_color: appearance.primary_color,
        secondary_color: appearance.secondary_color,
        logo: null,
        favicon: null,
    });

    useEffect(() => {
        return () => {
            if (logoPreview) URL.revokeObjectURL(logoPreview);
        };
    }, [logoPreview]);

    const handleLogoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setData('logo', file);
        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/settings/appearance', { forceFormData: true });
    };

    const resetForm = () => {
        reset();
        setLogoPreview(null);
    };

    return (
        <AppShell
            title="Appearance"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Appearance' },
            ]}
        >
            <PageHeader
                title="Appearance"
                description="Set the school identity and your preferred interface mode."
            />

            <form onSubmit={submit} className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <Card>
                    <CardHeader>
                        <CardTitle>School branding</CardTitle>
                        <CardDescription>
                            Brand colors stay consistent across light and dark mode.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="primary_color">Primary color</Label>
                                <Input
                                    id="primary_color"
                                    type="color"
                                    value={data.primary_color}
                                    onChange={(event) => setData('primary_color', event.target.value)}
                                    className="h-11 w-full cursor-pointer p-1"
                                />
                                {errors.primary_color && <p className="text-sm text-destructive">{errors.primary_color}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="secondary_color">Secondary color</Label>
                                <Input
                                    id="secondary_color"
                                    type="color"
                                    value={data.secondary_color}
                                    onChange={(event) => setData('secondary_color', event.target.value)}
                                    className="h-11 w-full cursor-pointer p-1"
                                />
                                {errors.secondary_color && <p className="text-sm text-destructive">{errors.secondary_color}</p>}
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="logo">School logo</Label>
                                <Input id="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" onChange={handleLogoChange} />
                                <p className="text-xs text-muted-foreground">PNG, JPG, SVG, or WebP up to 2 MB.</p>
                                {errors.logo && <p className="text-sm text-destructive">{errors.logo}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="favicon">Favicon</Label>
                                <Input id="favicon" type="file" accept="image/png,image/x-icon,image/webp,image/svg+xml" onChange={(event) => setData('favicon', event.target.files?.[0] ?? null)} />
                                <p className="text-xs text-muted-foreground">A compact square mark up to 512 KB.</p>
                                {errors.favicon && <p className="text-sm text-destructive">{errors.favicon}</p>}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="theme">Your interface mode</Label>
                            <select
                                id="theme"
                                value={data.theme}
                                onChange={(event) => setData('theme', event.target.value as Appearance['theme'])}
                                className="input w-full"
                            >
                                <option value="system">Use system preference</option>
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                            </select>
                            <p className="text-xs text-muted-foreground">
                                This changes your surfaces only. It does not change the school colors.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save appearance'}
                            </Button>
                            <Button type="button" variant="outline" onClick={resetForm} disabled={processing}>
                                Reset
                            </Button>
                            {recentlySuccessful && <p className="text-sm text-success">Appearance saved.</p>}
                        </div>
                    </CardContent>
                </Card>

                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Preview</CardTitle>
                        <CardDescription>Review the brand colors before saving.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-xl border border-border">
                            <div className="flex items-center gap-3 p-4" style={{ backgroundColor: data.primary_color }}>
                                <span className="flex size-10 items-center justify-center overflow-hidden rounded-lg bg-white/20 text-lg font-semibold text-white">
                                    {logoPreview ? <img src={logoPreview} alt="" className="size-full object-cover" /> : 'A'}
                                </span>
                                <span className="font-semibold text-white">School workspace</span>
                            </div>
                            <div className="space-y-3 bg-card p-4">
                                <div className="h-3 w-3/4 rounded-full bg-muted" />
                                <div className="h-3 w-1/2 rounded-full bg-muted" />
                                <button type="button" className="rounded-lg px-3 py-2 text-sm font-medium text-white" style={{ backgroundColor: data.secondary_color }}>
                                    Sample action
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AppShell>
    );
}
