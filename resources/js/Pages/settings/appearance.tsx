// Facts for this file:
// 1. Called by: routes/web.php (GET and POST /settings/appearance). The old
//    /settings/theme screen redirects here, so this is the only theme editor.
// 2. Data flow: reads props (appearance, themeConfig, themeModes, userThemeMode);
//    posts theme (personal preference), light/dark token maps (school palette)
//    and theme_config (every public website colour) in one request.
// 3. All labels come from resources/js/lib/i18n/copy.ts, so the screen is
//    bilingual and RTL-safe.

import { useEffect, useMemo, useState, type ChangeEvent, type CSSProperties, type FormEvent } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { t, tk, type CopyKey, type Locale } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import {
    DEFAULT_PALETTES,
    KNOWN_COLOR_TOKENS,
    WEBSITE_COLOR_GROUPS,
    canHoldGradient,
    getContrastingColor,
    humaniseTokenKey,
    isHexColor,
    normalisePalettes,
    serialisePalettes,
    tokenBackground,
    tokenBaseColor,
    type Palettes,
    type RawThemeModes,
    type ThemeMode,
    type TokenGradient,
    type TokenKey,
    type TokenValue,
} from '@/lib/theme';

type Mode = 'light' | 'dark';

/** The five headline tokens, in the order the palette card lists them. */
const TOKENS: TokenKey[] = ['accent', 'background', 'surface', 'text', 'muted'];

/** The largest stop list the editor allows, so a saved gradient stays readable. */
const MAX_STOPS = 6;

/**
 * A brand-new gradient starts as the token's own colour at both ends, so
 * switching to Gradient never looks like a colour change until the operator
 * moves a stop.
 */
function starterGradient(color: string): TokenGradient {
    return {
        type: 'linear',
        angle: 0,
        stops: [
            { color, position: 0 },
            { color, position: 100 },
        ],
    };
}

type Appearance = {
    theme: 'light' | 'dark' | 'system';
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    logo_path?: string | null;
    favicon_path?: string | null;
};

type ThemeConfig = Record<string, string>;

type AppearanceProps = {
    appearance: Appearance;
    themeConfig: ThemeConfig;
    themeModes: RawThemeModes;
    userThemeMode: ThemeMode;
};

/**
 * A labelled colour control: the label sits above the swatch and hex input, and
 * every part can shrink, so a long label or a long value wraps inside its grid
 * cell instead of overlapping its neighbour.
 */
function ColorField({
    id,
    label,
    value,
    onChange,
    disabled = false,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (next: string) => void;
    disabled?: boolean;
}) {
    const swatch = isHexColor(value) ? value : '#ffffff';

    return (
        <div className="min-w-0 space-y-1.5">
            <Label htmlFor={id} className="block truncate" title={label}>
                {label}
            </Label>
            <div className="flex min-w-0 items-center gap-2">
                <input
                    type="color"
                    aria-label={label}
                    title={label}
                    value={swatch}
                    disabled={disabled}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-9 w-10 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-0.5 disabled:cursor-not-allowed disabled:opacity-40"
                />
                <Input
                    id={id}
                    value={value}
                    disabled={disabled}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-9 min-w-0 flex-1 font-mono text-xs"
                    spellCheck={false}
                />
            </div>
        </div>
    );
}

/**
 * The dictionary label for a token key, falling back to a humanised version of
 * the key itself so a token the registry does not know yet is still readable.
 */
function labelFor(locale: Locale, key: string): string {
    const copyKey = `settings.appearance.color.${key}`;
    const label = tk(locale, copyKey);

    return label === copyKey ? humaniseTokenKey(key) : label;
}

/** A labelled free-text control, for the tokens that are not colours. */
function TextField({
    id,
    label,
    value,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (next: string) => void;
}) {
    return (
        <div className="min-w-0 space-y-1.5">
            <Label htmlFor={id} className="block truncate" title={label}>
                {label}
            </Label>
            <Input
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="h-9 w-full font-mono text-xs"
                spellCheck={false}
            />
        </div>
    );
}

type PreviewTokens = Record<string, string>;

const PREVIEW_NAV: CopyKey[] = ['public.about', 'public.programs', 'public.admissions'];

/** Reads a website token with a design-system fallback. */
function token(tokens: PreviewTokens, key: string, fallback: string): string {
    const value = tokens[key];

    return value && value !== '' ? value : fallback;
}

/**
 * A compact mock of the public site: header, hero with both buttons, a card and
 * the footer band, painted with the tokens the operator is editing.
 */
function WebsitePreview({ tokens }: { tokens: PreviewTokens }) {
    const { locale } = useLocale();

    return (
        <div
            className="overflow-hidden rounded-lg border"
            style={{
                backgroundColor: token(tokens, 'colorBackground', '#faf9f5'),
                borderColor: token(tokens, 'colorBorder', '#e6e1d3'),
            }}
        >
            <div
                className="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5"
                style={{
                    backgroundColor: token(tokens, 'colorHeader', '#ffffff'),
                    color: token(tokens, 'colorHeaderForeground', '#1c1a16'),
                    borderBottom: `1px solid ${token(tokens, 'colorHeaderBorder', '#e6e1d3')}`,
                }}
            >
                <span className="flex items-center gap-2">
                    <span
                        className="flex size-5 shrink-0 items-center justify-center rounded-md text-[0.6rem] font-semibold"
                        style={{
                            backgroundColor: token(tokens, 'colorPrimary', '#0a5c42'),
                            color: token(tokens, 'colorPrimaryForeground', '#ffffff'),
                        }}
                    >
                        A
                    </span>
                    <span className="truncate text-sm font-semibold">
                        {t(locale, 'settings.appearance.preview.websiteName')}
                    </span>
                </span>
                <span className="flex items-center gap-3 text-[0.7rem]">
                    {PREVIEW_NAV.map((key) => (
                        <span key={key} style={{ color: token(tokens, 'colorForeground', '#1c1a16') }}>
                            {t(locale, key)}
                        </span>
                    ))}
                </span>
            </div>

            <div className="space-y-3 px-4 py-5">
                <p
                    className="text-[0.65rem] font-semibold uppercase tracking-[0.18em]"
                    style={{ color: token(tokens, 'colorMutedForeground', '#74705f') }}
                >
                    {t(locale, 'public.about')}
                </p>
                <p
                    className="text-base font-semibold"
                    style={{ color: token(tokens, 'colorForeground', '#1c1a16') }}
                >
                    {t(locale, 'settings.appearance.preview.websiteName')}
                </p>
                <p className="text-xs" style={{ color: token(tokens, 'colorMutedForeground', '#74705f') }}>
                    {t(locale, 'settings.appearance.preview.websiteTagline')}
                </p>

                <div className="flex flex-wrap items-center gap-2">
                    <span
                        className="rounded-lg px-3 py-1.5 text-xs font-medium"
                        style={{
                            backgroundColor: token(tokens, 'colorButtonPrimary', '#0a5c42'),
                            color: token(tokens, 'colorButtonPrimaryForeground', '#ffffff'),
                        }}
                    >
                        {t(locale, 'settings.appearance.preview.websiteCta')}
                    </span>
                    <span
                        className="rounded-lg border px-3 py-1.5 text-xs font-medium"
                        style={{
                            backgroundColor: token(tokens, 'colorButtonSecondary', '#f2efe8'),
                            color: token(tokens, 'colorButtonSecondaryForeground', '#29261f'),
                            borderColor: token(tokens, 'colorBorder', '#e6e1d3'),
                        }}
                    >
                        {t(locale, 'settings.appearance.preview.websiteSecondary')}
                    </span>
                    <span
                        className="text-xs font-medium underline underline-offset-2"
                        style={{ color: token(tokens, 'colorLink', '#0a5c42') }}
                    >
                        {t(locale, 'public.learnMore')}
                    </span>
                </div>

                <div
                    className="rounded-lg border p-3"
                    style={{
                        backgroundColor: token(tokens, 'colorCard', '#ffffff'),
                        borderColor: token(tokens, 'colorBorder', '#e6e1d3'),
                    }}
                >
                    <p
                        className="text-sm font-medium"
                        style={{ color: token(tokens, 'colorCardForeground', '#1c1a16') }}
                    >
                        {t(locale, 'settings.appearance.preview.websiteCardTitle')}
                    </p>
                    <p className="mt-1 text-xs" style={{ color: token(tokens, 'colorMutedForeground', '#74705f') }}>
                        {t(locale, 'settings.appearance.preview.websiteCardBody')}
                    </p>
                </div>
            </div>

            <div
                className="px-4 py-3 text-xs"
                style={{
                    backgroundColor: token(tokens, 'colorFooter', '#06281e'),
                    color: token(tokens, 'colorFooterForeground', '#f4f5f2'),
                    borderTop: `1px solid ${token(tokens, 'colorFooterBorder', '#1c533e')}`,
                }}
            >
                {t(locale, 'settings.appearance.preview.websiteFooter')}
            </div>
        </div>
    );
}

export default function AppearanceSettings({ appearance, themeConfig, themeModes, userThemeMode }: AppearanceProps) {
    const { locale } = useLocale();
    // The controller redirects with a flash message, so the confirmation comes
    // from the session rather than from Inertia's two-second form state, which a
    // redirect re-render can drop before the operator ever sees it.
    const { flash } = usePage<App.PageProps>().props;
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [palettes, setPalettes] = useState<Palettes>(() => normalisePalettes(themeModes));
    const [themeModeState, setThemeModeState] = useState<ThemeMode>(userThemeMode);
    const [websiteTokens, setWebsiteTokens] = useState<ThemeConfig>(themeConfig);
    const [activePaletteMode, setActivePaletteMode] = useState<Mode>('light');

    useEffect(() => {
        return () => {
            if (logoPreview) URL.revokeObjectURL(logoPreview);
        };
    }, [logoPreview]);

    // A fresh page load (or a save that redirects back) is the source of truth.
    useEffect(() => {
        setWebsiteTokens(themeConfig);
    }, [themeConfig]);

    const {
        setData,
        post,
        transform,
        processing,
        errors,
        recentlySuccessful,
        reset,
    } = useForm<{
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
        light: serialisePalettes(normalisePalettes(themeModes)).light,
        dark: serialisePalettes(normalisePalettes(themeModes)).dark,
    });

    useEffect(() => {
        setData('theme', themeModeState);
    }, [themeModeState, setData]);

    useEffect(() => {
        const serialised = serialisePalettes(palettes);
        setData('light', serialised.light);
        setData('dark', serialised.dark);
    }, [palettes, setData]);

    useEffect(() => {
        setData('primary_color', websiteTokens.colorPrimary ?? appearance.primary_color);
        setData('secondary_color', websiteTokens.colorSecondary ?? appearance.secondary_color);
        setData('accent_color', websiteTokens.colorAccent ?? appearance.accent_color);
        setData('theme_config', JSON.stringify(websiteTokens));
    }, [websiteTokens, appearance, setData]);

    const handleLogoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setData('logo', file);
        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleFaviconChange = (event: ChangeEvent<HTMLInputElement>) => {
        setData('favicon', event.target.files?.[0] ?? null);
    };

    /** Applies a patch to one token of one colour mode. */
    const patchToken = (mode: Mode, key: TokenKey, patch: Partial<TokenValue>) => {
        setPalettes((prev) => ({
            ...prev,
            [mode]: {
                ...prev[mode],
                [key]: { ...prev[mode][key], ...patch },
            },
        }));
    };

    /** Applies a patch to the gradient of one token, if it has one. */
    const patchGradient = (mode: Mode, key: TokenKey, patch: Partial<TokenGradient>) => {
        setPalettes((prev) => {
            const tokenValue = prev[mode][key];
            if (!tokenValue.gradient) return prev;

            return {
                ...prev,
                [mode]: {
                    ...prev[mode],
                    [key]: { ...tokenValue, gradient: { ...tokenValue.gradient, ...patch } },
                },
            };
        });
    };

    const patchStop = (mode: Mode, key: TokenKey, index: number, patch: Partial<TokenGradient['stops'][number]>) => {
        setPalettes((prev) => {
            const gradient = prev[mode][key].gradient;
            if (!gradient) return prev;

            const stops = gradient.stops.map((stop, position) =>
                position === index ? { ...stop, ...patch } : stop
            );

            return {
                ...prev,
                [mode]: { ...prev[mode], [key]: { ...prev[mode][key], gradient: { ...gradient, stops } } },
            };
        });
    };

    const addStop = (mode: Mode, key: TokenKey) => {
        setPalettes((prev) => {
            const gradient = prev[mode][key].gradient;
            if (!gradient || gradient.stops.length >= MAX_STOPS) return prev;

            const last = gradient.stops[gradient.stops.length - 1];
            const stops = [...gradient.stops, { color: last.color, position: Math.min(100, last.position + 10) }];

            return {
                ...prev,
                [mode]: { ...prev[mode], [key]: { ...prev[mode][key], gradient: { ...gradient, stops } } },
            };
        });
    };

    const removeStop = (mode: Mode, key: TokenKey, index: number) => {
        setPalettes((prev) => {
            const gradient = prev[mode][key].gradient;
            // Two stops are the minimum a gradient needs to exist at all.
            if (!gradient || gradient.stops.length <= 2) return prev;

            const stops = gradient.stops.filter((_, position) => position !== index);

            return {
                ...prev,
                [mode]: { ...prev[mode], [key]: { ...prev[mode][key], gradient: { ...gradient, stops } } },
            };
        });
    };

    /**
     * Flips a painting token between one flat colour and a gradient. Turning the
     * gradient off keeps `token.color`, so nothing is lost by toggling back.
     */
    const handleFillChange = (mode: Mode, key: TokenKey, useGradient: boolean) => {
        setPalettes((prev) => {
            const tokenValue = prev[mode][key];

            if (useGradient) {
                return {
                    ...prev,
                    [mode]: {
                        ...prev[mode],
                        [key]: {
                            ...tokenValue,
                            gradient: tokenValue.gradient ?? starterGradient(tokenBaseColor(tokenValue)),
                        },
                    },
                };
            }

            return {
                ...prev,
                [mode]: { ...prev[mode], [key]: { ...tokenValue, gradient: null } },
            };
        });
    };

    const setWebsiteToken = (key: string, value: string) => {
        setWebsiteTokens((prev) => ({ ...prev, [key]: value }));
    };

    /**
     * Everything in `theme_config` that the colour registry does not list, split
     * into the colour-free tokens (fonts, radii, shadows) and any stray colour.
     */
    const { otherTokens, advancedTokens } = useMemo(() => {
        const rest = Object.keys(websiteTokens).filter((key) => !KNOWN_COLOR_TOKENS.has(key));

        return {
            otherTokens: rest.filter((key) => key.startsWith('color')),
            advancedTokens: rest.filter((key) => !key.startsWith('color')),
        };
    }, [websiteTokens]);

    /** Resets only the mode on screen, so the other palette is left alone. */
    const resetPalette = () => {
        setPalettes((prev) => ({ ...prev, [activePaletteMode]: DEFAULT_PALETTES[activePaletteMode] }));
    };

    const resetWebsiteTokens = () => setWebsiteTokens(themeConfig);

    const resetForm = () => {
        reset();
        setLogoPreview(null);
        setPalettes(normalisePalettes(themeModes));
        setWebsiteTokens(themeConfig);
        setThemeModeState(userThemeMode);
        setActivePaletteMode('light');
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        transform((data) => ({ ...data, theme_config: JSON.stringify(websiteTokens) }));
        post('/settings/appearance', { forceFormData: true });
    };

    const activePalette = palettes[activePaletteMode];
    const accentContrast = getContrastingColor(tokenBaseColor(activePalette.accent));

    /** The palette tokens as CSS variables, for the dashboard preview. */
    const dashboardPreviewStyle: CSSProperties = {
        background: tokenBackground(activePalette.background),
        borderColor: tokenBaseColor(activePalette.text),
        color: tokenBackground(activePalette.text),
    };

    return (
        <AppShell
            title={t(locale, 'settings.appearance.title')}
            breadcrumbs={[
                { label: t(locale, 'nav.settings'), href: '/settings/general' },
                { label: t(locale, 'nav.appearance') },
            ]}
        >
            <PageHeader
                title={t(locale, 'settings.appearance.title')}
                description={t(locale, 'settings.appearance.description')}
            />

            <form onSubmit={submit} className="mx-auto mt-6 max-w-5xl space-y-6">
                {/* A rejected save used to be silent: the controller validates
                    every token, so the reasons are listed here. */}
                {Object.keys(errors).length > 0 && (
                    <div role="alert" className="rounded-md border border-destructive/40 bg-destructive/5 p-3">
                        <p className="text-sm font-medium text-destructive">
                            {t(locale, 'settings.appearance.errors')}
                        </p>
                        <ul className="mt-1 list-inside list-disc text-xs text-destructive">
                            {Object.entries(errors).map(([field, message]) => (
                                <li key={field}>
                                    <span className="font-medium">{field}</span>: {message}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* 1. Brand */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t(locale, 'settings.appearance.brand.title')}</CardTitle>
                        <CardDescription>{t(locale, 'settings.appearance.brand.description')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="min-w-0 space-y-2">
                                <Label htmlFor="logo">{t(locale, 'settings.appearance.brand.logo')}</Label>
                                <Input
                                    id="logo"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    onChange={handleLogoChange}
                                />
                                <p className="text-xs text-muted-foreground">{t(locale, 'settings.appearance.brand.logoHelp')}</p>
                                {errors.logo && <p className="text-sm text-destructive">{errors.logo}</p>}
                                {logoPreview && (
                                    <img
                                        src={logoPreview}
                                        alt=""
                                        className="mt-2 h-12 w-auto rounded border object-contain"
                                    />
                                )}
                            </div>
                            <div className="min-w-0 space-y-2">
                                <Label htmlFor="favicon">{t(locale, 'settings.appearance.brand.favicon')}</Label>
                                <Input
                                    id="favicon"
                                    type="file"
                                    accept="image/png,image/x-icon,image/webp,image/svg+xml"
                                    onChange={handleFaviconChange}
                                />
                                <p className="text-xs text-muted-foreground">{t(locale, 'settings.appearance.brand.faviconHelp')}</p>
                                {errors.favicon && <p className="text-sm text-destructive">{errors.favicon}</p>}
                            </div>
                        </div>

                        <div className="grid gap-2 md:max-w-md">
                            <Label htmlFor="theme">{t(locale, 'settings.appearance.brand.mode')}</Label>
                            <Select
                                id="theme"
                                value={themeModeState}
                                onChange={(event) => setThemeModeState(event.target.value as ThemeMode)}
                            >
                                <option value="light">{t(locale, 'settings.appearance.mode.light')}</option>
                                <option value="dark">{t(locale, 'settings.appearance.mode.dark')}</option>
                                <option value="system">{t(locale, 'settings.appearance.mode.system')}</option>
                            </Select>
                            <p className="text-xs text-muted-foreground">{t(locale, 'settings.appearance.brand.modeHelp')}</p>
                            {errors.theme && <p className="text-sm text-destructive">{errors.theme}</p>}
                        </div>
                    </CardContent>
                </Card>

                {/* 2. Dashboard palette — one card, two modes */}
                <Card>
                    <CardHeader className="gap-3">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <CardTitle>{t(locale, 'settings.appearance.palette.title')}</CardTitle>
                                <CardDescription className="mt-1.5">
                                    {t(locale, 'settings.appearance.palette.description')}
                                </CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" onClick={resetPalette}>
                                {t(locale, 'settings.appearance.palette.reset')}
                            </Button>
                        </div>

                        <div
                            role="group"
                            aria-label={t(locale, 'settings.appearance.palette.title')}
                            className="inline-flex w-fit items-center gap-0.5 rounded-md bg-muted/40 p-0.5"
                        >
                            {(['light', 'dark'] as Mode[]).map((mode) => (
                                <button
                                    key={mode}
                                    type="button"
                                    aria-pressed={activePaletteMode === mode}
                                    onClick={() => setActivePaletteMode(mode)}
                                    className={cn(
                                        'rounded px-3 py-1 text-sm font-medium transition-colors',
                                        activePaletteMode === mode
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground'
                                    )}
                                >
                                    {t(locale, mode === 'light' ? 'settings.appearance.palette.light' : 'settings.appearance.palette.dark')}
                                </button>
                            ))}
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        {TOKENS.map((key) => {
                            const tokenValue = activePalette[key];
                            const gradientCapable = canHoldGradient(key);
                            const gradient = tokenValue.gradient;
                            const fill = gradient ? 'gradient' : 'solid';
                            const label = tk(locale, `settings.appearance.token.${key}`);

                            return (
                                <div key={key} className="space-y-3 rounded-lg border border-border/60 p-3">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <label
                                            htmlFor={`${activePaletteMode}-${key}`}
                                            className="text-sm font-medium text-foreground"
                                        >
                                            {label}
                                        </label>

                                        {gradientCapable && (
                                            <div
                                                role="group"
                                                aria-label={tk(locale, 'settings.appearance.palette.fillAria').replace('{token}', label)}
                                                className="flex items-center gap-0.5 rounded-md bg-muted/40 p-0.5"
                                            >
                                                <button
                                                    type="button"
                                                    aria-pressed={fill === 'solid'}
                                                    onClick={() => handleFillChange(activePaletteMode, key, false)}
                                                    className={cn(
                                                        'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                                                        fill === 'solid'
                                                            ? 'bg-background text-foreground shadow-sm'
                                                            : 'text-muted-foreground hover:text-foreground'
                                                    )}
                                                >
                                                    {t(locale, 'settings.appearance.palette.solid')}
                                                </button>
                                                <button
                                                    type="button"
                                                    aria-pressed={fill === 'gradient'}
                                                    onClick={() => handleFillChange(activePaletteMode, key, true)}
                                                    className={cn(
                                                        'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                                                        fill === 'gradient'
                                                            ? 'bg-background text-foreground shadow-sm'
                                                            : 'text-muted-foreground hover:text-foreground'
                                                    )}
                                                >
                                                    {t(locale, 'settings.appearance.palette.gradient')}
                                                </button>
                                            </div>
                                        )}
                                    </div>

                                    {fill === 'solid' && (
                                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                                            <input
                                                type="color"
                                                aria-label={label}
                                                title={label}
                                                value={tokenValue.color}
                                                onChange={(event) =>
                                                    patchToken(activePaletteMode, key, { color: event.target.value })
                                                }
                                                className="h-9 w-10 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-0.5"
                                            />

                                            <Input
                                                id={`${activePaletteMode}-${key}`}
                                                value={tokenValue.color}
                                                onChange={(event) =>
                                                    patchToken(activePaletteMode, key, { color: event.target.value })
                                                }
                                                className="h-9 min-w-0 flex-1 font-mono text-xs"
                                                spellCheck={false}
                                            />

                                            <label
                                                htmlFor={`${activePaletteMode}-${key}-opaque`}
                                                title={t(locale, 'settings.appearance.palette.opaqueHelp')}
                                                className="flex shrink-0 cursor-pointer items-center gap-1.5 rounded-md border border-input px-2 py-1.5 text-xs text-muted-foreground"
                                            >
                                                <input
                                                    id={`${activePaletteMode}-${key}-opaque`}
                                                    type="checkbox"
                                                    className="size-3.5 accent-[var(--color-primary)]"
                                                    checked={tokenValue.solid}
                                                    onChange={(event) =>
                                                        patchToken(activePaletteMode, key, { solid: event.target.checked })
                                                    }
                                                />
                                                {t(locale, 'settings.appearance.palette.opaque')}
                                            </label>
                                        </div>
                                    )}

                                    {gradient && (
                                        <div className="space-y-3">
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div className="min-w-0 space-y-1.5">
                                                    <Label
                                                        htmlFor={`${activePaletteMode}-${key}-gradient-type`}
                                                        className="text-xs"
                                                    >
                                                        {t(locale, 'settings.appearance.gradient.type')}
                                                    </Label>
                                                    <Select
                                                        id={`${activePaletteMode}-${key}-gradient-type`}
                                                        value={gradient.type}
                                                        onChange={(event) =>
                                                            patchGradient(activePaletteMode, key, {
                                                                type: event.target.value === 'radial' ? 'radial' : 'linear',
                                                            })
                                                        }
                                                    >
                                                        <option value="linear">
                                                            {t(locale, 'settings.appearance.gradient.linear')}
                                                        </option>
                                                        <option value="radial">
                                                            {t(locale, 'settings.appearance.gradient.radial')}
                                                        </option>
                                                    </Select>
                                                </div>

                                                {gradient.type === 'linear' && (
                                                    <div className="min-w-0 space-y-1.5">
                                                        <Label
                                                            htmlFor={`${activePaletteMode}-${key}-gradient-angle`}
                                                            className="text-xs"
                                                        >
                                                            {tk(locale, 'settings.appearance.gradient.angle').replace(
                                                                '{degrees}',
                                                                String(gradient.angle)
                                                            )}
                                                        </Label>
                                                        <input
                                                            id={`${activePaletteMode}-${key}-gradient-angle`}
                                                            type="range"
                                                            min={0}
                                                            max={360}
                                                            step={5}
                                                            value={gradient.angle}
                                                            onChange={(event) =>
                                                                patchGradient(activePaletteMode, key, {
                                                                    angle: Number(event.target.value),
                                                                })
                                                            }
                                                            className="w-full accent-[var(--color-primary)]"
                                                        />
                                                    </div>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <Label className="text-xs">
                                                        {t(locale, 'settings.appearance.gradient.stops')}
                                                    </Label>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={gradient.stops.length >= MAX_STOPS}
                                                        onClick={() => addStop(activePaletteMode, key)}
                                                    >
                                                        <Plus className="size-3.5" aria-hidden="true" />
                                                        {t(locale, 'settings.appearance.gradient.addStop')}
                                                    </Button>
                                                </div>

                                                {gradient.stops.map((stop, index) => (
                                                    <div key={`${key}-stop-${index}`} className="flex min-w-0 items-center gap-2">
                                                        <input
                                                            type="color"
                                                            aria-label={tk(locale, 'settings.appearance.gradient.stopColor').replace(
                                                                '{index}',
                                                                String(index + 1)
                                                            )}
                                                            value={stop.color}
                                                            onChange={(event) =>
                                                                patchStop(activePaletteMode, key, index, {
                                                                    color: event.target.value,
                                                                })
                                                            }
                                                            className="h-8 w-9 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-0.5"
                                                        />

                                                        <Input
                                                            aria-label={tk(locale, 'settings.appearance.gradient.stopHex').replace(
                                                                '{index}',
                                                                String(index + 1)
                                                            )}
                                                            value={stop.color}
                                                            onChange={(event) =>
                                                                patchStop(activePaletteMode, key, index, {
                                                                    color: event.target.value,
                                                                })
                                                            }
                                                            className="h-8 w-28 shrink-0 font-mono text-xs"
                                                            spellCheck={false}
                                                        />

                                                        <input
                                                            type="range"
                                                            aria-label={tk(
                                                                locale,
                                                                'settings.appearance.gradient.stopPosition'
                                                            ).replace('{index}', String(index + 1))}
                                                            min={0}
                                                            max={100}
                                                            value={stop.position}
                                                            onChange={(event) =>
                                                                patchStop(activePaletteMode, key, index, {
                                                                    position: Number(event.target.value),
                                                                })
                                                            }
                                                            className="min-w-0 flex-1 accent-[var(--color-primary)]"
                                                        />

                                                        <span className="w-10 shrink-0 text-end text-xs tabular-nums text-muted-foreground">
                                                            {stop.position}%
                                                        </span>

                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon-sm"
                                                            aria-label={tk(locale, 'settings.appearance.gradient.removeStop').replace(
                                                                '{index}',
                                                                String(index + 1)
                                                            )}
                                                            disabled={gradient.stops.length <= 2}
                                                            onClick={() => removeStop(activePaletteMode, key, index)}
                                                        >
                                                            <Trash2 className="size-4" aria-hidden="true" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>

                                            <div
                                                aria-hidden="true"
                                                className="h-10 rounded-md border border-border/60"
                                                style={{ background: tokenBackground(tokenValue) }}
                                            />
                                            <code className="block break-all text-xs text-muted-foreground">
                                                {tokenBackground(tokenValue)}
                                            </code>
                                        </div>
                                    )}

                                    {fill === 'solid' && (
                                        <div
                                            aria-hidden="true"
                                            className="h-8 rounded-md border border-border/60"
                                            style={{ background: tokenBackground(tokenValue) }}
                                        />
                                    )}

                                    <p className="text-xs text-muted-foreground">
                                        {tk(locale, `settings.appearance.token.${key}Hint`)}
                                    </p>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                {/* 3. Public website colours — every token, grouped and labelled */}
                <Card>
                    <CardHeader className="gap-3">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <CardTitle>{t(locale, 'settings.appearance.website.title')}</CardTitle>
                                <CardDescription className="mt-1.5">
                                    {t(locale, 'settings.appearance.website.description')}
                                </CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" onClick={resetWebsiteTokens}>
                                {t(locale, 'settings.appearance.reset')}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-8">
                        {WEBSITE_COLOR_GROUPS.map((group) => (
                            <section key={group.id} className="space-y-3">
                                <h3 className="text-sm font-semibold text-foreground">
                                    {t(locale, group.labelKey)}
                                </h3>
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {group.tokens.map((key) => (
                                        <ColorField
                                            key={key}
                                            id={key}
                                            label={labelFor(locale, key)}
                                            value={websiteTokens[key] ?? ''}
                                            onChange={(next) => setWebsiteToken(key, next)}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}

                        {otherTokens.length > 0 && (
                            <section className="space-y-3">
                                <h3 className="text-sm font-semibold text-foreground">
                                    {t(locale, 'settings.appearance.website.other')}
                                </h3>
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {otherTokens.map((key) => (
                                        <ColorField
                                            key={key}
                                            id={key}
                                            label={labelFor(locale, key)}
                                            value={websiteTokens[key] ?? ''}
                                            onChange={(next) => setWebsiteToken(key, next)}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}

                        {advancedTokens.length > 0 && (
                            <details className="rounded-lg border border-border/60 p-3">
                                <summary className="cursor-pointer text-sm font-medium text-foreground">
                                    {t(locale, 'settings.appearance.website.advanced')}
                                </summary>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {t(locale, 'settings.appearance.website.advancedHelp')}
                                </p>
                                <div className="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {advancedTokens.map((key) => (
                                        <TextField
                                            key={key}
                                            id={`advanced-${key}`}
                                            label={humaniseTokenKey(key)}
                                            value={websiteTokens[key] ?? ''}
                                            onChange={(next) => setWebsiteToken(key, next)}
                                        />
                                    ))}
                                </div>
                            </details>
                        )}
                    </CardContent>
                </Card>

                {/* 4. Previews */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t(locale, 'settings.appearance.preview.title')}</CardTitle>
                        <CardDescription>{t(locale, 'settings.appearance.preview.description')}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-6 lg:grid-cols-2">
                        <div className="min-w-0 space-y-2">
                            <p className="text-sm font-medium text-foreground">
                                {t(locale, 'settings.appearance.preview.dashboard')}
                            </p>
                            <div className="rounded-xl border p-4" style={dashboardPreviewStyle}>
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <p
                                        className="text-base font-semibold"
                                        style={{ color: tokenBaseColor(activePalette.text) }}
                                    >
                                        {t(locale, 'settings.appearance.preview.dashboard')}
                                    </p>
                                    <span
                                        className="flex size-8 shrink-0 items-center justify-center rounded-full"
                                        style={{
                                            background: tokenBackground(activePalette.accent),
                                            color: accentContrast,
                                        }}
                                    >
                                        <span className="size-2.5 rounded-full bg-current" />
                                    </span>
                                </div>
                                <div
                                    className="mt-3 rounded-lg border p-3"
                                    style={{
                                        background: tokenBackground(activePalette.surface),
                                        borderColor: tokenBaseColor(activePalette.text),
                                    }}
                                >
                                    <p
                                        className="text-xs font-medium"
                                        style={{ color: tokenBaseColor(activePalette.text) }}
                                    >
                                        {t(locale, 'settings.appearance.token.surface')}
                                    </p>
                                    <p
                                        className="mt-1 text-[0.7rem]"
                                        style={{ color: tokenBaseColor(activePalette.muted) }}
                                    >
                                        {t(locale, 'settings.appearance.token.surfaceHint')}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="min-w-0 space-y-2">
                            <p className="text-sm font-medium text-foreground">
                                {t(locale, 'settings.appearance.preview.website')}
                            </p>
                            <WebsitePreview tokens={websiteTokens} />
                        </div>
                    </CardContent>
                </Card>

                {/* 5. One save for the whole screen */}
                <div className="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                        {processing
                            ? t(locale, 'settings.appearance.saving')
                            : t(locale, 'settings.appearance.save')}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={resetForm}
                        disabled={processing}
                        className="w-full sm:w-auto"
                    >
                        {t(locale, 'settings.appearance.reset')}
                    </Button>
                    {(flash?.success || recentlySuccessful) && (
                        <p role="status" className="text-sm text-success">
                            {flash?.success ?? t(locale, 'settings.appearance.saved')}
                        </p>
                    )}
                </div>
            </form>
        </AppShell>
    );
}
