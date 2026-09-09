import { useEffect, useState, type ChangeEvent, type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabList, Tab, TabPanels, TabPanel } from '@/components/ui/tabs';
import PublicSitePreview from './public-site-preview';

/**
 * Token groups for the Website Colors tab. Each group is a visual cluster of
 * tokens the public site consumes together.
 */
const GROUPED_WEB_TOKEN_SECTIONS: { title: string; tokenKeys: readonly (typeof WEB_PRIMARY_KEYS)[number][] }[] = [
    {
        title: 'Brand colors',
        tokenKeys: ['colorPrimary', 'colorPrimaryForeground', 'colorSecondary', 'colorSecondaryForeground', 'colorAccent', 'colorAccentForeground', 'colorLink', 'colorLinkHover'],
    },
    {
        title: 'Page surfaces',
        tokenKeys: ['colorForeground', 'colorBackground', 'colorCard', 'colorCardForeground', 'colorMuted', 'colorMutedForeground'],
    },
    {
        title: 'Borders & inputs',
        tokenKeys: ['colorBorder', 'colorInput', 'colorRing'],
    },
    {
        title: 'Sidebar',
        tokenKeys: ['colorSidebar', 'colorSidebarForeground', 'colorSidebarPrimary', 'colorSidebarPrimaryForeground', 'colorSidebarAccent', 'colorSidebarAccentForeground', 'colorSidebarBorder', 'colorSidebarRing'],
    },
    {
        title: 'Header & footer',
        tokenKeys: ['colorHeader', 'colorHeaderForeground', 'colorHeaderBorder', 'colorFooter', 'colorFooterForeground', 'colorFooterBorder'],
    },
    {
        title: 'Buttons',
        tokenKeys: ['colorButtonPrimary', 'colorButtonPrimaryForeground', 'colorButtonSecondary', 'colorButtonSecondaryForeground'],
    },
    {
        title: 'Feedback states',
        tokenKeys: ['colorSuccess', 'colorSuccessForeground', 'colorWarning', 'colorWarningForeground', 'colorError', 'colorErrorForeground'],
    },
];
const labelForTokenKey = (key: (typeof WEB_PRIMARY_KEYS)[number]): string =>
    key
        .replace(/^color/, '')
        .replace(/([A-Z])/g, ' $1')
        .trim()
        .replace(/Fore/, ' text')
        .replace(/Bg|Back|Input/, '')
        .replace(/Sidebar/, ' sidebar')
        .replace(/Header|Footer/, (m) => m.toLowerCase())
        .replace(/Primary|Secondary|Accent/, (m) => m.toLowerCase())
        .replace(/Success|Warning|Error|Info/, (m) => m.toLowerCase());

// Token keys the website actually consumes (subset of the full design-system
// token set the server shares). Editing these controls the public-facing site.
const WEB_PRIMARY_KEYS = [
    'colorPrimary',
    'colorPrimaryForeground',
    'colorSecondary',
    'colorSecondaryForeground',
    'colorAccent',
    'colorAccentForeground',
    'colorForeground',
    'colorBackground',
    'colorCard',
    'colorCardForeground',
    'colorMuted',
    'colorMutedForeground',
    'colorBorder',
    'colorInput',
    'colorRing',
    'colorLink',
    'colorLinkHover',
    'colorSidebar',
    'colorSidebarForeground',
    'colorSidebarPrimary',
    'colorSidebarPrimaryForeground',
    'colorSidebarAccent',
    'colorSidebarAccentForeground',
    'colorSidebarBorder',
    'colorSidebarRing',
    'colorHeader',
    'colorHeaderForeground',
    'colorHeaderBorder',
    'colorFooter',
    'colorFooterForeground',
    'colorFooterBorder',
    'colorButtonPrimary',
    'colorButtonPrimaryForeground',
    'colorButtonSecondary',
    'colorButtonSecondaryForeground',
    'colorInputBackground',
    'colorInputForeground',
    'colorInputBorder',
    'colorInputFocus',
    'colorSuccess',
    'colorSuccessForeground',
    'colorWarning',
    'colorWarningForeground',
    'colorError',
    'colorErrorForeground',
] as const;

type Appearance = {
    theme: 'light';
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    logo_path?: string | null;
    favicon_path?: string | null;
};

type ThemeConfig = { [key: string]: string; };

type AppearanceProps = {
    appearance: Appearance;
    themeConfig: ThemeConfig;
};

export default function AppearanceSettings({ appearance, themeConfig }: AppearanceProps) {
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [themeConfigState, setThemeConfigState] = useState<ThemeConfig>(themeConfig);
    const { data, setData, post, transform, processing, errors, recentlySuccessful, reset } = useForm<{
        primary_color: string;
        secondary_color: string;
        accent_color: string;
        logo: File | null;
        favicon: File | null;
        theme_config: string;
    }>({
        primary_color: appearance.primary_color,
        secondary_color: appearance.secondary_color,
        accent_color: appearance.accent_color,
        logo: null,
        favicon: null,
        theme_config: '',
    });

    useEffect(() => {
        return () => {
            if (logoPreview) URL.revokeObjectURL(logoPreview);
        };
    }, [logoPreview]);

    // Synchronize themeConfigState with the themeConfig prop when it changes
    useEffect(() => {
        setThemeConfigState(themeConfig);
    }, [themeConfig]);

    // When the themeConfigState changes, update the form data for the backward compatible fields
    useEffect(() => {
        setData('primary_color', themeConfigState.colorPrimary ?? appearance.primary_color);
        setData('secondary_color', themeConfigState.colorSecondary ?? appearance.secondary_color);
        setData('accent_color', themeConfigState.colorAccent ?? appearance.accent_color);
    }, [themeConfigState, appearance]);

    const handleLogoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setData('logo', file);
        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleFaviconChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setData('favicon', file);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        // Send the theme configuration as a JSON string with this submission.
        // (transform() avoids racing setData() against post(), which could
        // submit a stale empty theme_config and fail JSON validation.)
        transform((data) => ({ ...data, theme_config: JSON.stringify(themeConfigState) }));
        post('/settings/appearance', { forceFormData: true });
    };

    const resetForm = () => {
        reset();
        setLogoPreview(null);
        setThemeConfigState(themeConfig); // Reset to the initial themeConfig from props
    };

    return (
        <AppShell
            title="Appearance"
            breadcrumbs={[
                {
                    label: 'Settings',
                    href: '/settings',
                },
                {
                    label: 'Appearance',
                },
            ]}
        >
            <PageHeader>
                Appearance settings
            </PageHeader>

            <form onSubmit={submit} className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                {/* General tab */}
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                        <CardDescription>Upload your school logo and favicon.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="logo">School logo</Label>
                                <Input id="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" onChange={handleLogoChange} />
                                <p className="text-xs text-muted-foreground">PNG, JPG, SVG, or WebP up to 2 MB.</p>
                                {errors.logo && <p className="text-sm text-destructive">{errors.logo}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="favicon">Favicon</Label>
                                <Input id="favicon" type="file" accept="image/png,image/x-icon,image/webp,image/svg+xml" onChange={handleFaviconChange} />
                                <p className="text-xs text-muted-foreground">A compact square mark up to 512 KB.</p>
                                {errors.favicon && <p className="text-sm text-destructive">{errors.favicon}</p>}
                            </div>
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

                {/* Website colors editor â€” full set of tokens the public site consumes. */}
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Website Colors</CardTitle>
                        <CardDescription>
                            These tokens control the public marketing site: hero, buttons, footer,
                            sidebar, header, cards, links, and brand accents. The dashboard itself
                            reads the same tokens.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <Tabs defaultValue="grouped" className="w-full">
                            <TabList className="grid w-full grid-cols-2 border-b">
                                <Tab
                                    value="grouped"
                                    className="flex h-10 w-0 flex-1 items-center justify-between rounded-t-lg border-b-2 px-4 text-sm font-medium hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:border-b-foreground"
                                >
                                    Grouped
                                </Tab>
                                <Tab
                                    value="all"
                                    className="flex h-10 w-0 flex-1 items-center justify-between rounded-t-lg border-b-2 px-4 text-sm font-medium hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:border-b-foreground"
                                >
                                    All tokens
                                </Tab>
                            </TabList>
                            <TabPanels className="space-y-6 pt-4">
                                <TabPanel value="grouped" className="space-y-6 pt-4">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {GROUPED_WEB_TOKEN_SECTIONS.map((group) => {
                                            const { title, tokenKeys } = group;
                                            return (
                                                <div key={title} className="space-y-4">
                                                    <Label className="text-sm font-medium">{title}</Label>
                                                    <div className="grid gap-2 md:grid-cols-3">
                                                        {tokenKeys.map((key) => {
                                                            const value = themeConfigState[key] ?? '';
                                                            return (
                                                                <div key={key} className="space-y-1.5">
                                                                    <Label htmlFor={`wb-${key}`}>{labelForTokenKey(key)}</Label>
                                                                    <Input
                                                                        id={`wb-${key}`}
                                                                        type="color"
                                                                        value={value || '#000000'}
                                                                        onChange={(e) => {
                                                                            setThemeConfigState((prev) => ({
                                                                                ...prev,
                                                                                [key]: e.target.value,
                                                                            }));
                                                                        }}
                                                                        className="h-11 w-full cursor-pointer p-1"
                                                                    />
                                                                    <p className="text-xs text-muted-foreground font-mono">{key}</p>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </TabPanel>
                                <TabPanel value="all" className="space-y-6 pt-4">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {WEB_PRIMARY_KEYS.map((key) => {
                                                            const value = themeConfigState[key] ?? '';
                                                            return (
                                                                <div key={key} className="space-y-1.5">
                                                                    <Label htmlFor={`web-all-${key}`}>{labelForTokenKey(key)}</Label>
                                                                    <Input
                                                                        id={`web-all-${key}`}
                                                                        type="color"
                                                                        value={value || '#000000'}
                                                                        onChange={(e) => {
                                                                            setThemeConfigState((prev) => ({
                                                                                ...prev,
                                                                                [key]: e.target.value,
                                                                            }));
                                                                        }}
                                                                        className="h-11 w-full cursor-pointer p-1"
                                                                    />
                                                                    <p className="text-xs text-muted-foreground font-mono">{key}</p>
                                                                </div>
                                                            );
                                                        })}
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </CardContent>
                </Card>

                {/* Public site preview â€” renders the marketing chrome with the current tokens. */}
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Public site preview</CardTitle>
                        <CardDescription>The marketing site as it will look with these colors.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <PublicSitePreview tokens={themeConfigState} />
                    </CardContent>
                </Card>
            </form>
        </AppShell>
    );
}
