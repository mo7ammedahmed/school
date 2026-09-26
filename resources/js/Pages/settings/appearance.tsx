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
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';

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
    theme: 'light' | 'dark' | 'system';
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    logo_path?: string | null;
    favicon_path?: string | null;
};

type ThemeConfig = { [key: string]: string };

type ThemeModes = {
    light: Record<string, string | boolean>;
    dark: Record<string, string | boolean>;
};

type AppearanceProps = {
    appearance: Appearance;
    themeConfig: ThemeConfig;
    themeModes: ThemeModes;
    userThemeMode: string;
};

export default function AppearanceSettings({ appearance, themeConfig, themeModes, userThemeMode }: AppearanceProps) {
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [themeConfigState, setThemeConfigState] = useState<ThemeConfig>(themeConfig);
    const [advancedOptions, setAdvancedOptions] = useState<boolean>(false);
    const [themeModeState, setThemeModeState] = useState<'light' | 'dark' | 'system'>(userThemeMode);
    const { setData, post, transform, processing, errors, recentlySuccessful, reset } = useForm<{
        primary_color: string;
        secondary_color: string;
        accent_color: string;
        logo: File | null;
        favicon: File | null;
        theme_config: string;
        theme: 'light' | 'dark' | 'system';
        light: Record<string, string | boolean>;
        dark: Record<string, string | boolean>;
    }>({
        primary_color: appearance.primary_color,
        secondary_color: appearance.secondary_color,
        accent_color: appearance.accent_color,
        logo: null,
        favicon: null,
        theme_config: '',
        theme: appearance.theme,
        light: themeModes.light,
        dark: themeModes.dark,
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

    // Update theme mode when changed
    useEffect(() => {
        setData('theme', themeModeState);
    }, [themeModeState]);

    // Update light/dark theme modes when themeConfigState changes (for backward compatibility)
    useEffect(() => {
        // Only update if we're not in advanced mode (to avoid conflicts)
        if (!advancedOptions) {
            setData('light', {
                ...themeModes.light,
                colorPrimary: themeConfigState.colorPrimary ?? themeModes.light.colorPrimary,
                colorSecondary: themeConfigState.colorSecondary ?? themeModes.light.colorSecondary,
                colorAccent: themeConfigState.colorAccent ?? themeModes.light.colorAccent,
                colorForeground: themeConfigState.colorForeground ?? themeModes.light.colorForeground,
                colorBackground: themeConfigState.colorBackground ?? themeModes.light.colorBackground,
                colorSurface: themeConfigState.colorCard ?? themeModes.light.colorSurface,
                colorMuted: themeConfigState.colorMuted ?? themeModes.light.colorMuted,
            });
            setData('dark', {
                ...themeModes.dark,
                colorPrimary: themeConfigState.colorPrimary ?? themeModes.dark.colorPrimary,
                colorSecondary: themeConfigState.colorSecondary ?? themeModes.dark.colorSecondary,
                colorAccent: themeConfigState.colorAccent ?? themeModes.dark.colorAccent,
                colorForeground: themeConfigState.colorForeground ?? themeModes.dark.colorForeground,
                colorBackground: themeConfigState.colorBackground ?? themeModes.dark.colorBackground,
                colorSurface: themeConfigState.colorCard ?? themeModes.dark.colorSurface,
                colorMuted: themeConfigState.colorMuted ?? themeModes.dark.colorMuted,
            });
        }
    }, [themeConfigState, advancedOptions, themeModes]);

    const handleLogoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setData('logo', file);
        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleFaviconChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setData('favicon', file);
    };

    const handleThemeModeChange = (value: 'light' | 'dark' | 'system') => {
        setThemeModeState(value);
    };

    const toggleAdvancedOptions = () => {
        setAdvancedOptions(!advancedOptions);
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
        setThemeModeState(userThemeMode);
        setData('light', themeModes.light);
        setData('dark', themeModes.dark);
    };

    return (
        <AppShell
            title="Appearance"
            breadcrumbs={[
                {
                    label: 'Settings',
                    href: '/settings/general',
                },
                {
                    label: 'Appearance',
                },
            ]}
        >
            <PageHeader
                title="Appearance & Theme"
                description="Customize your school's branding, colors, and theme appearance."
            />

            <form onSubmit={submit} className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                {/* General tab */}
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                        <CardDescription>Upload your school logo and favicon, and set basic appearance options.</CardDescription>
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

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="theme">Theme mode</Label>
                                <Select
                                    id="theme"
                                    value={themeModeState}
                                    onValueChange={handleThemeModeChange}
                                    options={[
                                        { label: 'Light', value: 'light' },
                                        { label: 'Dark', value: 'dark' },
                                        { label: 'System', value: 'system' },
                                    ]}
                                />
                                <p className="text-xs text-muted-foreground">Choose how the dashboard appears for users.</p>
                                {errors.theme && <p className="text-sm text-destructive">{errors.theme}</p>}
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="advanced-toggle"
                                    checked={advancedOptions}
                                    onChange={toggleAdvancedOptions}
                                />
                                <Label htmlFor="advanced-toggle" className="text-sm font-medium">
                                    Show advanced options
                                </Label>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save appearance & theme'}
                            </Button>
                            <Button type="button" variant="outline" onClick={resetForm} disabled={processing}>
                                Reset
                            </Button>
                            {recentlySuccessful && <p className="text-sm text-success">Settings saved successfully.</p>}
                        </div>
                    </CardContent>
                </Card>

                {/* Advanced options - only show when toggled */}
                {advancedOptions && (
                    <>
                        {/* Website colors editor */}
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

                        {/* Public site preview */}
                        <Card className="h-fit">
                            <CardHeader>
                                <CardTitle>Public site preview</CardTitle>
                                <CardDescription>The marketing site as it will look with these colors.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <PublicSitePreview tokens={themeConfigState} />
                            </CardContent>
                        </Card>
                    </>
                )}

                {/* Theme mode advanced options */}
                {advancedOptions && (
                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle>Theme Mode Settings</CardTitle>
                            <CardDescription>Fine-tune the light and dark color palettes for the dashboard interface.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-semibold">Light Mode</h3>
                                        <div className="flex items-center space-x-2">
                                                            <Button variant="outline" size="sm" onClick={() => {
                                                                setData('light', {
                                                                    ...themeModes.light,
                                                                    colorPrimary: '#3b82f6',
                                                                    colorSecondary: '#64748b',
                                                                    colorAccent: '#10b981',
                                                                    colorForeground: '#1f2937',
                                                                    colorBackground: '#ffffff',
                                                                    colorSurface: '#f9fafb',
                                                                    colorMuted: '#6b7280',
                                                                });
                                                            }}>
                                                                Reset to defaults
                                                            </Button>
                                        </div>
                                    </div>
                                    <div className="grid gap-4">
                                        {/* Accent color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="light-accent">Accent color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="light-accent"
                                                    type="color"
                                                    value={themeModes.light.colorPrimary ?? '#3b82f6'}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorPrimary: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="light-accent-value"
                                                    type="text"
                                                    value={themeModes.light.colorPrimary ?? '#3b82f6'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="light-accent-solid"
                                                    checked={themeModes.light.colorPrimary_solid !== false}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorPrimary_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="light-accent-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Background color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="light-background">Background color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="light-background"
                                                    type="color"
                                                    value={themeModes.light.colorBackground ?? '#ffffff'}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorBackground: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="light-background-value"
                                                    type="text"
                                                    value={themeModes.light.colorBackground ?? '#ffffff'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="light-background-solid"
                                                    checked={themeModes.light.colorBackground_solid !== false}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorBackground_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="light-background-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Surface color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="light-surface">Surface color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="light-surface"
                                                    type="color"
                                                    value={themeModes.light.colorSurface ?? '#f9fafb'}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorSurface: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="light-surface-value"
                                                    type="text"
                                                    value={themeModes.light.colorSurface ?? '#f9fafb'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="light-surface-solid"
                                                    checked={themeModes.light.colorSurface_solid !== false}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorSurface_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="light-surface-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Text color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="light-text">Text color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="light-text"
                                                    type="color"
                                                    value={themeModes.light.colorForeground ?? '#1f2937'}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorForeground: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="light-text-value"
                                                    type="text"
                                                    value={themeModes.light.colorForeground ?? '#1f2937'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="light-text-solid"
                                                    checked={themeModes.light.colorForeground_solid !== false}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorForeground_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="light-text-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Muted color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="light-muted">Muted color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="light-muted"
                                                    type="color"
                                                    value={themeModes.light.colorMuted ?? '#6b7280'}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorMuted: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="light-muted-value"
                                                    type="text"
                                                    value={themeModes.light.colorMuted ?? '#6b7280'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="light-muted-solid"
                                                    checked={themeModes.light.colorMuted_solid !== false}
                                                    onChange={(e) => {
                                                        setData('light', {
                                                            ...themeModes.light,
                                                            colorMuted_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="light-muted-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-semibold">Dark Mode</h3>
                                        <div className="flex items-center space-x-2">
                                                            <Button variant="outline" size="sm" onClick={() => {
                                                                setData('dark', {
                                                                    ...themeModes.dark,
                                                                    colorPrimary: '#3b82f6',
                                                                    colorSecondary: '#64748b',
                                                                    colorAccent: '#10b981',
                                                                    colorForeground: '#f9fafb',
                                                                    colorBackground: '#111827',
                                                                    colorSurface: '#1f2937',
                                                                    colorMuted: '#6b7280',
                                                                });
                                                            }}>
                                                                Reset to defaults
                                                            </Button>
                                        </div>
                                    </div>
                                    <div className="grid gap-4">
                                        {/* Accent color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="dark-accent">Accent color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="dark-accent"
                                                    type="color"
                                                    value={themeModes.dark.colorPrimary ?? '#3b82f6'}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorPrimary: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="dark-accent-value"
                                                    type="text"
                                                    value={themeModes.dark.colorPrimary ?? '#3b82f6'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="dark-accent-solid"
                                                    checked={themeModes.dark.colorPrimary_solid !== false}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorPrimary_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="dark-accent-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Background color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="dark-background">Background color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="dark-background"
                                                    type="color"
                                                    value={themeModes.dark.colorBackground ?? '#111827'}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorBackground: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="dark-background-value"
                                                    type="text"
                                                    value={themeModes.dark.colorBackground ?? '#111827'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="dark-background-solid"
                                                    checked={themeModes.dark.colorBackground_solid !== false}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorBackground_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="dark-background-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Surface color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="dark-surface">Surface color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="dark-surface"
                                                    type="color"
                                                    value={themeModes.dark.colorSurface ?? '#1f2937'}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorSurface: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="dark-surface-value"
                                                    type="text"
                                                    value={themeModes.dark.colorSurface ?? '#1f2937'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="dark-surface-solid"
                                                    checked={themeModes.dark.colorSurface_solid !== false}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorSurface_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="dark-surface-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Text color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="dark-text">Text color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="dark-text"
                                                    type="color"
                                                    value={themeModes.dark.colorForeground ?? '#f9fafb'}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorForeground: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="dark-text-value"
                                                    type="text"
                                                    value={themeModes.dark.colorForeground ?? '#f9fafb'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="dark-text-solid"
                                                    checked={themeModes.dark.colorForeground_solid !== false}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorForeground_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="dark-text-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>

                                        {/* Muted color */}
                                        <div className="space-y-3">
                                            <Label htmlFor="dark-muted">Muted color</Label>
                                            <div className="flex items-center gap-3">
                                                <Input
                                                    id="dark-muted"
                                                    type="color"
                                                    value={themeModes.dark.colorMuted ?? '#6b7280'}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorMuted: e.target.value,
                                                        });
                                                    }}
                                                    className="h-10 w-20 cursor-pointer p-1"
                                                />
                                                <Input
                                                    id="dark-muted-value"
                                                    type="text"
                                                    value={themeModes.dark.colorMuted ?? '#6b7280'}
                                                    readOnly
                                                    className="h-10 flex-1 font-mono text-xs text-center bg-muted"
                                                />
                                                <Checkbox
                                                    id="dark-muted-solid"
                                                    checked={themeModes.dark.colorMuted_solid !== false}
                                                    onChange={(e) => {
                                                        setData('dark', {
                                                            ...themeModes.dark,
                                                            colorMuted_solid: e.target.checked,
                                                        });
                                                    }}
                                                />
                                                <Label htmlFor="dark-muted-solid" className="text-xs">Solid</Label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </form>
        </AppShell>
    );
}