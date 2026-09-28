/**
 * Builds the whole website palette from a handful of colours.
 *
 * The Appearance screen used to ask for fifty-odd colours, which meant every
 * school either hand-picked values that clashed or left the defaults and never
 * matched their own brand. Only the five seeds are chosen now; every other token
 * is derived from them with the same rules, so a palette stays coherent whatever
 * the school picks — and any token can still be overridden by hand, one at a
 * time, from the derived list.
 *
 * Everything here is plain functions over hex strings: the screen needs to
 * preview the result as the operator types, and the same values are what get
 * stored in `theme_config`.
 */

export type Palette = Record<string, string>;

const WHITE = '#ffffff';
const BLACK = '#000000';

/** The colours a school actually chooses. Everything else follows from these. */
export const SEED_KEYS = [
    'colorPrimary',
    'colorSecondary',
    'colorAccent',
    'colorBackground',
    'colorForeground',
] as const;

export type SeedKey = (typeof SEED_KEYS)[number];

export const DEFAULT_SEEDS: Record<SeedKey, string> = {
    colorPrimary: '#0a5c42',
    colorSecondary: '#f2efe8',
    colorAccent: '#efecdf',
    colorBackground: '#faf9f5',
    colorForeground: '#1c1a16',
};

type Rgb = [number, number, number];

function parse(hex: string | undefined | null): Rgb | null {
    const match = /^#?([0-9a-f]{6})$/i.exec((hex ?? '').trim());

    if (!match) return null;

    return [0, 2, 4].map((offset) => parseInt(match[1].slice(offset, offset + 2), 16)) as Rgb;
}

function toHex([r, g, b]: Rgb): string {
    const channel = (value: number) =>
        Math.min(255, Math.max(0, Math.round(value)))
            .toString(16)
            .padStart(2, '0');

    return `#${channel(r)}${channel(g)}${channel(b)}`;
}

/** `weight` is how much of `b` ends up in the result, 0 to 1. */
export function mix(a: string, b: string, weight: number): string {
    const from = parse(a);
    const to = parse(b);

    if (!from || !to) return a;

    const ratio = Math.min(1, Math.max(0, weight));

    return toHex([
        from[0] + (to[0] - from[0]) * ratio,
        from[1] + (to[1] - from[1]) * ratio,
        from[2] + (to[2] - from[2]) * ratio,
    ]);
}

/** Positive lightens towards white, negative darkens towards black. */
export function shade(color: string, amount: number): string {
    return amount >= 0 ? mix(color, WHITE, amount) : mix(color, BLACK, -amount);
}

/** Relative luminance, the same measure `ColorService` uses on the server. */
export function luminance(color: string): number {
    const rgb = parse(color);

    if (!rgb) return 0;

    const [r, g, b] = rgb.map((channel) => {
        const value = channel / 255;

        return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function isDark(color: string): boolean {
    return luminance(color) < 0.45;
}

function contrast(a: string, b: string): number {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

/** Picks whichever of two candidate colours is readable on the given fill. */
export function readableOn(background: string, dark: string, light = WHITE): string {
    return contrast(background, light) >= contrast(background, dark) ? light : dark;
}

/**
 * The complete palette for a set of seeds.
 *
 * Neutral tokens fade from the page colour towards the text colour, so a school
 * that picks a warm background gets warm borders and muted text rather than the
 * grey a fixed rule would produce.
 */
export function deriveWebsitePalette(seeds: Partial<Palette> = {}): Palette {
    const primary = seeds.colorPrimary ?? DEFAULT_SEEDS.colorPrimary;
    const secondary = seeds.colorSecondary ?? DEFAULT_SEEDS.colorSecondary;
    const accent = seeds.colorAccent ?? DEFAULT_SEEDS.colorAccent;
    const background = seeds.colorBackground ?? DEFAULT_SEEDS.colorBackground;
    const foreground = seeds.colorForeground ?? DEFAULT_SEEDS.colorForeground;

    const dark = isDark(background);

    const card = mix(background, WHITE, dark ? 0.08 : 0.72);
    const muted = mix(background, foreground, dark ? 0.12 : 0.06);
    const border = mix(background, foreground, dark ? 0.2 : 0.14);
    const mutedForeground = mix(foreground, background, 0.42);
    const inputBorder = mix(background, foreground, dark ? 0.26 : 0.2);

    // The footer is the one surface that should read as a different room, so it
    // is the brand colour taken most of the way to black.
    const footer = mix(primary, BLACK, 0.62);

    const onPrimary = readableOn(primary, foreground);

    return {
        // Brand
        colorPrimary: primary,
        colorPrimaryForeground: onPrimary,
        colorSecondary: secondary,
        colorSecondaryForeground: readableOn(secondary, foreground),
        colorAccent: accent,
        colorAccentForeground: readableOn(accent, foreground),

        // Surfaces
        colorBackground: background,
        colorForeground: foreground,
        colorCard: card,
        colorCardForeground: foreground,
        colorPopover: card,
        colorPopoverForeground: foreground,
        colorMuted: muted,
        colorMutedForeground: mutedForeground,

        // Links
        colorLink: primary,
        colorLinkHover: shade(primary, -0.14),

        // Borders and inputs
        colorBorder: border,
        colorInput: border,
        colorRing: primary,
        colorInputBackground: card,
        colorInputForeground: foreground,
        colorInputPlaceholder: mutedForeground,
        colorInputBorder: inputBorder,
        colorInputFocus: primary,

        // Sidebar
        colorSidebar: card,
        colorSidebarForeground: foreground,
        colorSidebarPrimary: primary,
        colorSidebarPrimaryForeground: onPrimary,
        colorSidebarAccent: muted,
        colorSidebarAccentForeground: foreground,
        colorSidebarBorder: border,
        colorSidebarRing: primary,

        // Header
        colorHeader: card,
        colorHeaderForeground: foreground,
        colorHeaderBorder: border,

        // Footer
        colorFooter: footer,
        colorFooterForeground: mix(WHITE, footer, 0.08),
        colorFooterBorder: mix(footer, WHITE, 0.16),

        // Tables
        colorTableHeader: muted,
        colorTableHeaderForeground: foreground,
        colorTableRow: card,
        colorTableRowHover: mix(background, foreground, dark ? 0.16 : 0.03),
        colorTableBorder: border,

        // Buttons
        colorButtonPrimary: primary,
        colorButtonPrimaryForeground: onPrimary,
        colorButtonSecondary: secondary,
        colorButtonSecondaryForeground: readableOn(secondary, foreground),
        colorButtonOutline: card,
        colorButtonGhost: 'transparent',

        // Feedback keeps its own semantic colours: a warning that follows the
        // brand would stop reading as a warning.
        colorSuccess: '#15803d',
        colorSuccessForeground: WHITE,
        colorWarning: '#b45309',
        colorWarningForeground: WHITE,
        colorError: '#b42318',
        colorErrorForeground: WHITE,
        colorInfo: '#0e7490',
        colorInfoForeground: WHITE,
    };
}

/** The seeds in a stored palette, falling back to the defaults. */
export function seedsFrom(tokens: Palette): Record<SeedKey, string> {
    const seeds = { ...DEFAULT_SEEDS };

    for (const key of SEED_KEYS) {
        const value = tokens[key];

        if (typeof value === 'string' && parse(value)) {
            seeds[key] = value;
        }
    }

    return seeds;
}

/**
 * The tokens a school has deliberately set away from the derived value.
 *
 * A stored palette from before this screen was simplified contains all fifty-odd
 * colours; the ones that match what the seeds already produce are not real
 * choices, so they are dropped and the list reads as it should: a few seeds and
 * the exceptions.
 */
export function overridesFrom(
    tokens: Palette,
    seeds: Record<SeedKey, string>,
    baseline: Palette = {},
): Palette {
    const derived = deriveWebsitePalette(seeds);
    const overrides: Palette = {};

    for (const [key, value] of Object.entries(tokens)) {
        if (typeof value !== 'string' || !key.startsWith('color')) continue;
        if ((SEED_KEYS as readonly string[]).includes(key)) continue;
        // Equal to what the seeds produce, or to the design system's own default
        // that the old screen wrote wholesale: not a choice, so it stays derived.
        if (value === derived[key] || value === baseline[key]) continue;

        overrides[key] = value;
    }

    return overrides;
}

/** `theme_config` entries that are not colours: font stacks, radii, shadows. */
export function advancedFrom(tokens: Palette): Palette {
    return Object.fromEntries(
        Object.entries(tokens).filter(([key, value]) => !key.startsWith('color') && typeof value === 'string'),
    );
}

/** What gets stored: the derived palette with the school's exceptions on top. */
export function composePalette(
    seeds: Record<SeedKey, string>,
    overrides: Palette,
    advanced: Palette,
): Palette {
    return { ...deriveWebsitePalette(seeds), ...overrides, ...advanced };
}
