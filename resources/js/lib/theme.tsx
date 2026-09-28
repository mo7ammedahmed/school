import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import type { CopyKey } from '@/lib/i18n/copy';

export type ThemeMode = 'light' | 'dark' | 'system';
export type ResolvedMode = 'light' | 'dark';

/** The five headline tokens a school can tune per colour mode. */
export const TOKEN_KEYS = ['accent', 'background', 'surface', 'text', 'muted'] as const;

export type TokenKey = (typeof TOKEN_KEYS)[number];

/**
 * Only the painting tokens can be a gradient. A gradient on text or on the
 * muted caption colour has no meaning, so the editor hides the option and the
 * applier ignores one if it somehow arrives.
 */
export const GRADIENT_TOKENS: readonly TokenKey[] = ['accent', 'background', 'surface'];

export function canHoldGradient(key: TokenKey): boolean {
    return GRADIENT_TOKENS.includes(key);
}

export type GradientStop = { color: string; position: number };

export type TokenGradient = {
    /** `linear` honours the angle; `radial` fades out from the top centre. */
    type: 'linear' | 'radial';
    angle: number;
    stops: GradientStop[];
};

/** A token is either one flat colour or a gradient — never both. */
export type TokenValue = {
    color: string;
    /** A tint renders semi-transparent, which is what the Solid switch turns off. */
    solid: boolean;
    gradient: TokenGradient | null;
};

export type ModeTokens = Record<TokenKey, TokenValue>;

export type Palettes = { light: ModeTokens; dark: ModeTokens };

/**
 * The raw shape the server stores and sends: `{ accent: '#006c55',
 * accent_solid: true, accent_gradient: '<json>' }`.
 */
export type RawThemeModes = Record<string, Record<string, string | boolean | null>>;

const DEFAULT_COLORS: Record<'light' | 'dark', Record<TokenKey, string>> = {
    light: { accent: '#006c55', background: '#f4f3ee', surface: '#ffffff', text: '#0a0a0a', muted: '#6b6b64' },
    dark: { accent: '#006c55', background: '#070707', surface: '#0b0b0b', text: '#f4f4f1', muted: '#a4a4a8' },
};

/** A fresh, fully solid palette for one colour mode. */
export function defaultMode(mode: 'light' | 'dark'): ModeTokens {
    return TOKEN_KEYS.reduce((tokens, key) => {
        tokens[key] = { color: DEFAULT_COLORS[mode][key], solid: true, gradient: null };
        return tokens;
    }, {} as ModeTokens);
}

export function defaultModes(): Palettes {
    return { light: defaultMode('light'), dark: defaultMode('dark') };
}

export const DEFAULT_PALETTES: Palettes = defaultModes();

/** Keeps a percentage inside the 0–100 range a CSS stop accepts. */
function clampPercent(value: number): number {
    if (!Number.isFinite(value)) return 0;
    return Math.min(100, Math.max(0, Math.round(value)));
}

const HEX_COLOR = /^#[0-9a-fA-F]{6}$/;

/** Reads back a gradient the editor stored as JSON, ignoring anything malformed. */
export function parseGradient(value: unknown): TokenGradient | null {
    if (typeof value !== 'string' || value.trim() === '') return null;

    let raw: unknown;

    try {
        raw = JSON.parse(value);
    } catch {
        return null;
    }

    if (typeof raw !== 'object' || raw === null) return null;

    const candidate = raw as Partial<TokenGradient>;
    const stops = Array.isArray(candidate.stops)
        ? candidate.stops
              .filter((stop): stop is GradientStop =>
                  typeof stop === 'object' && stop !== null && HEX_COLOR.test(String((stop as GradientStop).color))
              )
              .map((stop, index, all) => ({
                  color: stop.color,
                  // An omitted position spreads the stop between its neighbours.
                  position: clampPercent(Number.isFinite(Number(stop.position)) ? Number(stop.position) : (index / Math.max(1, all.length - 1)) * 100),
              }))
        : [];

    // A single stop is a flat colour, so the token should stay solid instead.
    if (stops.length < 2) return null;

    const type: TokenGradient['type'] = candidate.type === 'radial' ? 'radial' : 'linear';
    const angle = Number.isFinite(Number(candidate.angle)) ? Math.min(360, Math.max(0, Number(candidate.angle))) : 0;

    return { type, angle, stops };
}

/**
 * A multipart form turns a boolean into "1"/"0", so a truthy check would read
 * "0" as solid. Anything unrecognised falls back to the supplied default.
 */
export function parseSolid(raw: unknown, fallback = true): boolean {
    if (raw === undefined || raw === null || raw === '') return fallback;
    if (typeof raw === 'boolean') return raw;
    if (typeof raw === 'number') return raw !== 0;

    const text = String(raw).trim().toLowerCase();

    if (text === '0' || text === 'false' || text === 'off' || text === 'no') return false;
    if (text === '1' || text === 'true' || text === 'on' || text === 'yes') return true;

    return fallback;
}

/** One token out of the raw server payload. */
export function parseToken(raw: Record<string, string | boolean | null> | undefined, key: TokenKey, mode: 'light' | 'dark'): TokenValue {
    const source = raw ?? {};
    const color = typeof source[key] === 'string' && HEX_COLOR.test(source[key] as string)
        ? (source[key] as string)
        : DEFAULT_COLORS[mode][key];

    return {
        color,
        // Tokens saved before the Solid switch existed are solid.
        solid: parseSolid(source[`${key}_solid`], true),
        gradient: canHoldGradient(key) ? parseGradient(source[`${key}_gradient`]) : null,
    };
}

function parseMode(raw: Record<string, string | boolean | null> | undefined, mode: 'light' | 'dark'): ModeTokens {
    return TOKEN_KEYS.reduce((tokens, key) => {
        tokens[key] = parseToken(raw, key, mode);
        return tokens;
    }, {} as ModeTokens);
}

/** Turns the shared `themeModes` prop (or anything else) into a safe palette pair. */
export function normalisePalettes(raw?: RawThemeModes | null, fallback: Palettes = DEFAULT_PALETTES): Palettes {
    if (!raw?.light || !raw?.dark) return fallback;

    return { light: parseMode(raw.light, 'light'), dark: parseMode(raw.dark, 'dark') };
}

/** The flat record the Appearance form posts back to the server. */
export function serialiseMode(tokens: ModeTokens): Record<string, string | boolean> {
    return TOKEN_KEYS.reduce<Record<string, string | boolean>>((payload, key) => {
        payload[key] = tokens[key].color;
        payload[`${key}_solid`] = tokens[key].solid;
        payload[`${key}_gradient`] = tokens[key].gradient ? JSON.stringify(tokens[key].gradient) : '';
        return payload;
    }, {});
}

export function serialisePalettes(palettes: Palettes): Record<string, Record<string, string | boolean>> {
    return { light: serialiseMode(palettes.light), dark: serialiseMode(palettes.dark) };
}

/** The CSS gradient a token paints. */
export function gradientCss(gradient: TokenGradient): string {
    const stops = gradient.stops.map((stop) => `${stop.color} ${clampPercent(stop.position)}%`).join(', ');

    return gradient.type === 'radial'
        ? `radial-gradient(circle at 50% 0%, ${stops})`
        : `linear-gradient(${gradient.angle}deg, ${stops})`;
}

/**
 * What the token paints: its gradient, its colour, or a 55% tint of it when the
 * Solid switch is off.
 */
export function tokenBackground(token: TokenValue): string {
    if (token.gradient) return gradientCss(token.gradient);

    return token.solid ? token.color : `color-mix(in srgb, ${token.color} 55%, transparent)`;
}

/**
 * A gradient has no single colour, so anything that needs one (contrast, a
 * border, an email) reads the first stop.
 */
export function tokenBaseColor(token: TokenValue): string {
    return token.gradient?.stops[0]?.color ?? token.color;
}

/** Which CSS custom properties each headline token drives. */
const TOKEN_VARS: Record<TokenKey, string[]> = {
    accent: [
        '--color-primary',
        '--color-ring',
        '--color-input-focus',
        '--color-link',
        '--color-button-primary',
        '--color-sidebar-primary',
    ],
    background: ['--color-background'],
    surface: ['--color-card', '--color-popover', '--color-input-background', '--color-table-row'],
    text: [
        '--color-foreground',
        '--color-card-foreground',
        '--color-popover-foreground',
        '--color-input-foreground',
    ],
    muted: ['--color-muted-foreground', '--color-input-placeholder'],
};

/** Black or white, whichever reads best on the supplied background. */
export function getContrastingColor(hexColor: string): string {
    const cleanHex = hexColor.replace('#', '');
    if (cleanHex.length !== 6) return '#ffffff';

    const r = parseInt(cleanHex.substring(0, 2), 16);
    const g = parseInt(cleanHex.substring(2, 4), 16);
    const b = parseInt(cleanHex.substring(4, 6), 16);

    const channel = (value: number) => {
        const normalized = value / 255;
        return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
    };

    const luminance = 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);

    return 1.05 / (luminance + 0.05) >= (luminance + 0.05) / 0.05 ? '#ffffff' : '#000000';
}

export function prefersDark(): boolean {
    return typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function resolveMode(mode: ThemeMode): ResolvedMode {
    return mode === 'dark' || (mode === 'system' && prefersDark()) ? 'dark' : 'light';
}

/** Writes the palette for the active mode onto <html> as inline custom properties. */
export function applyPalette(tokens: ModeTokens): void {
    const root = document.documentElement;

    TOKEN_KEYS.forEach((token) => {
        const value = tokens[token];

        if (!value) return;

        const base = tokenBaseColor(value);

        TOKEN_VARS[token].forEach((cssVar) => root.style.setProperty(cssVar, base));

        // A gradient rides its own variable. `--color-*` has to stay a plain
        // colour: it is reused for borders, focus rings and text, where a
        // gradient would be invalid.
        root.style.setProperty(
            `--gradient-${token}`,
            value.gradient ? gradientCss(value.gradient) : 'none'
        );
    });

    const accent = tokenBaseColor(tokens.accent);

    root.style.setProperty('--color-primary-foreground', getContrastingColor(accent));
    root.style.setProperty('--color-button-primary-foreground', getContrastingColor(accent));
}

/**
 * The website tokens that carry the school's identity rather than a surface:
 * the brand colour and the semantics that should read the same in both modes.
 */
const BRAND_TOKENS = new Set([
    'colorPrimary',
    'colorPrimaryForeground',
    'colorLink',
    'colorLinkHover',
    'colorRing',
    'colorInputFocus',
    'colorButtonPrimary',
    'colorButtonPrimaryForeground',
    'colorSidebarPrimary',
    'colorSidebarPrimaryForeground',
    'colorSidebarRing',
    'colorSuccess',
    'colorSuccessForeground',
    'colorWarning',
    'colorWarningForeground',
    'colorError',
    'colorErrorForeground',
    'colorInfo',
    'colorInfoForeground',
]);

/**
 * Writes the school's website palette onto the page.
 *
 * `theme_config` was stored and shipped to the browser but never used: the
 * fifty-odd colours on the Appearance screen painted nothing, and the site was
 * really drawn by the five headline tokens. Every `colorXxx` key becomes its
 * `--color-xxx` custom property, so what the screen shows is what pages get.
 *
 * The surfaces were chosen against a light page, so in dark mode only the brand
 * tokens carry over and the neutrals stay with the dark palette — otherwise a
 * cream website palette would put white cards on a black dashboard.
 */
export function applyWebsitePalette(
    website?: Record<string, string> | null,
    mode: ResolvedMode = 'light',
): void {
    if (!website) return;

    const root = document.documentElement;

    for (const [key, value] of Object.entries(website)) {
        if (!key.startsWith('color') || typeof value !== 'string' || value === '') continue;
        if (mode === 'dark' && !BRAND_TOKENS.has(key)) continue;

        root.style.setProperty(
            `--${key.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase()}`,
            value,
        );
    }
}

/**
 * Single entry point that flips the mode class and applies the matching palette,
 * then the school's own colours on top so they are never the ones overwritten.
 */
export function applyTheme(
    mode: ThemeMode,
    palettes: Palettes,
    website?: Record<string, string> | null,
): void {
    const root = document.documentElement;
    const resolved = resolveMode(mode);

    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.theme = resolved;

    applyPalette(palettes[resolved]);
    applyWebsitePalette(website, resolved);
}

const STORAGE_KEY = 'aether.theme-mode';

export function readStoredMode(): ThemeMode | null {
    if (typeof window === 'undefined') return null;
    const stored = window.localStorage.getItem(STORAGE_KEY);
    return stored === 'light' || stored === 'dark' || stored === 'system' ? stored : null;
}

function storeMode(mode: ThemeMode): void {
    if (typeof window !== 'undefined') window.localStorage.setItem(STORAGE_KEY, mode);
}

/**
 * Whether the preference can be mirrored onto the server. A guest browsing the
 * public site has no profile to store it on, and posting to the authenticated
 * endpoint would bounce them to the login screen, so the toggle stays a local
 * (localStorage) preference until they sign in.
 */
let serverPersistence = true;

export function setServerPersistence(enabled: boolean): void {
    serverPersistence = enabled;
}

/** Persists the preference server-side so it follows the user between devices. */
export function persistMode(mode: ThemeMode): void {
    storeMode(mode);

    if (!serverPersistence) return;

    router.post('/settings/theme/mode', { mode }, { preserveState: true, preserveScroll: true, preserveUrl: true, onError: () => undefined });
}

type ThemeContextValue = {
    mode: ThemeMode;
    resolved: ResolvedMode;
    palettes: Palettes;
    setMode: (mode: ThemeMode) => void;
    toggle: () => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

type ThemeProviderProps = {
    children: ReactNode;
    initialMode: ThemeMode;
    palettes?: Palettes | null;
    /** The school's website colours, applied over the headline palette. */
    website?: Record<string, string> | null;
};

export function ThemeProvider({ children, initialMode, palettes, website }: ThemeProviderProps) {
    const [mode, setModeState] = useState<ThemeMode>(() => readStoredMode() ?? initialMode);
    const [resolved, setResolved] = useState<ResolvedMode>(() => resolveMode(readStoredMode() ?? initialMode));

    const activePalettes = useMemo<Palettes>(() => palettes ?? DEFAULT_PALETTES, [palettes]);

    // Apply on mount and whenever the mode or the school palettes change.
    useEffect(() => {
        applyTheme(mode, activePalettes, website);
        setResolved(resolveMode(mode));
    }, [mode, activePalettes, website]);

    // Follow the OS when the preference is "system".
    useEffect(() => {
        if (mode !== 'system') return;
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const onChange = () => {
            applyTheme('system', activePalettes, website);
            setResolved(resolveMode('system'));
        };
        media.addEventListener('change', onChange);
        return () => media.removeEventListener('change', onChange);
    }, [mode, activePalettes, website]);

    const setMode = useCallback((next: ThemeMode) => {
        setModeState(next);
        persistMode(next);
    }, []);

    const toggle = useCallback(() => {
        setModeState((current) => {
            const next: ThemeMode = resolveMode(current) === 'dark' ? 'light' : 'dark';
            persistMode(next);
            return next;
        });
    }, []);

    const value = useMemo<ThemeContextValue>(
        () => ({ mode, resolved, palettes: activePalettes, setMode, toggle }),
        [mode, resolved, activePalettes, setMode, toggle]
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

/** A six-digit hex colour, the only shape a swatch can edit directly. */
export function isHexColor(value: string | undefined | null): boolean {
    return typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value);
}

/**
 * One group of `color*` tokens on the Appearance screen. Grouping is what keeps
 * fifty-odd colours readable: a heading, then a grid of labelled controls.
 */
export type WebsiteTokenGroup = {
    id: string;
    labelKey: CopyKey;
    tokens: string[];
};

/**
 * Every colour the school stores in `theme_config`, in the order the Appearance
 * screen lists them. The registry lives next to the palette model so there is a
 * single source of truth for token names on the client.
 */
export const WEBSITE_COLOR_GROUPS: WebsiteTokenGroup[] = [
    {
        id: 'brand',
        labelKey: 'settings.appearance.group.brand',
        tokens: [
            'colorPrimary',
            'colorPrimaryForeground',
            'colorSecondary',
            'colorSecondaryForeground',
            'colorAccent',
            'colorAccentForeground',
        ],
    },
    {
        id: 'surfaces',
        labelKey: 'settings.appearance.group.surfaces',
        tokens: [
            'colorBackground',
            'colorForeground',
            'colorCard',
            'colorCardForeground',
            'colorPopover',
            'colorPopoverForeground',
            'colorMuted',
            'colorMutedForeground',
        ],
    },
    {
        id: 'links',
        labelKey: 'settings.appearance.group.links',
        tokens: ['colorLink', 'colorLinkHover'],
    },
    {
        id: 'borders',
        labelKey: 'settings.appearance.group.borders',
        tokens: [
            'colorBorder',
            'colorInput',
            'colorRing',
            'colorInputBackground',
            'colorInputForeground',
            'colorInputPlaceholder',
            'colorInputBorder',
            'colorInputFocus',
        ],
    },
    {
        id: 'sidebar',
        labelKey: 'settings.appearance.group.sidebar',
        tokens: [
            'colorSidebar',
            'colorSidebarForeground',
            'colorSidebarPrimary',
            'colorSidebarPrimaryForeground',
            'colorSidebarAccent',
            'colorSidebarAccentForeground',
            'colorSidebarBorder',
            'colorSidebarRing',
        ],
    },
    {
        id: 'header',
        labelKey: 'settings.appearance.group.header',
        tokens: ['colorHeader', 'colorHeaderForeground', 'colorHeaderBorder'],
    },
    {
        id: 'footer',
        labelKey: 'settings.appearance.group.footer',
        tokens: ['colorFooter', 'colorFooterForeground', 'colorFooterBorder'],
    },
    {
        id: 'table',
        labelKey: 'settings.appearance.group.table',
        tokens: [
            'colorTableHeader',
            'colorTableHeaderForeground',
            'colorTableRow',
            'colorTableRowHover',
            'colorTableBorder',
        ],
    },
    {
        id: 'button',
        labelKey: 'settings.appearance.group.button',
        tokens: [
            'colorButtonPrimary',
            'colorButtonPrimaryForeground',
            'colorButtonSecondary',
            'colorButtonSecondaryForeground',
            'colorButtonOutline',
            'colorButtonGhost',
        ],
    },
    {
        id: 'feedback',
        labelKey: 'settings.appearance.group.feedback',
        tokens: [
            'colorSuccess',
            'colorSuccessForeground',
            'colorWarning',
            'colorWarningForeground',
            'colorError',
            'colorErrorForeground',
            'colorInfo',
            'colorInfoForeground',
        ],
    },
];

/** The colour tokens the registry knows about, as one flat set. */
export const KNOWN_COLOR_TOKENS: ReadonlySet<string> = new Set(
    WEBSITE_COLOR_GROUPS.flatMap((group) => group.tokens)
);

/**
 * Splits `colorSidebarPrimaryForeground` into `Sidebar primary foreground`, so
 * anything the registry does not name yet still gets a readable label.
 */
export function humaniseTokenKey(key: string): string {
    const bare = key.replace(/^color/, '').replace(/^([a-z])/, (first) => first.toUpperCase());
    const words = bare
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        // `radius2xl` should read "Radius 2xl", not "Radius2xl".
        .replace(/([A-Za-z])([0-9])/g, '$1 $2');

    return words.charAt(0) + words.slice(1);
}

export function useThemeMode(): ThemeContextValue {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useThemeMode must be used inside a ThemeProvider');
    }
    return context;
}
