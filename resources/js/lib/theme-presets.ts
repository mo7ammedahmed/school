/**
 * The few choices behind the font, corner and shadow tokens.
 *
 * Everything that is not a colour used to be fifteen free-text fields holding
 * raw CSS — a font stack per role, five radii and five shadows, each written out
 * in full. Nobody changing a school's look wants to type
 *
 *     "Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, Cambria, serif
 *
 * so each family of tokens is offered as a named choice instead. The values are
 * the same ones the platform shipped with, so picking the middle option is the
 * same as changing nothing, and the raw fields stay available underneath.
 */

import type { CopyKey } from './i18n/copy';

export type Preset = {
    id: string;
    /** Dictionary key for the name shown on the button. */
    labelKey: CopyKey;
    /** The tokens this choice sets, exactly as they are stored. */
    tokens: Record<string, string>;
};

const SANS_OUTFIT =
    '"Outfit", "IBM Plex Sans Arabic", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';
const SANS_SYSTEM =
    'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "IBM Plex Sans Arabic", "Noto Sans Arabic", sans-serif';
const SERIF_FRAUNCES = '"Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, Cambria, serif';
const SERIF_SYSTEM = 'ui-serif, Georgia, Cambria, "Times New Roman", "Amiri", serif';
const ARABIC_PLEX = '"IBM Plex Sans Arabic", "Outfit", ui-sans-serif, system-ui, sans-serif';
const MONO_PLEX = '"IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, monospace';
const MONO_SYSTEM = 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

/** Font stacks for the five roles the design system names. */
export const FONT_PRESETS: Preset[] = [
    {
        id: 'outfit',
        labelKey: 'settings.appearance.preset.font.outfit',
        tokens: {
            fontSans: SANS_OUTFIT,
            fontSerif: SERIF_FRAUNCES,
            fontArabic: ARABIC_PLEX,
            fontDisplay: SERIF_FRAUNCES,
            fontMono: MONO_PLEX,
        },
    },
    {
        id: 'editorial',
        labelKey: 'settings.appearance.preset.font.editorial',
        tokens: {
            fontSans: '"IBM Plex Sans Arabic", "Outfit", ui-sans-serif, system-ui, sans-serif',
            fontSerif: SERIF_FRAUNCES,
            fontArabic: ARABIC_PLEX,
            fontDisplay: SERIF_FRAUNCES,
            fontMono: MONO_PLEX,
        },
    },
    {
        id: 'system',
        labelKey: 'settings.appearance.preset.font.system',
        tokens: {
            fontSans: SANS_SYSTEM,
            fontSerif: SERIF_SYSTEM,
            fontArabic: ARABIC_PLEX,
            fontDisplay: SERIF_SYSTEM,
            fontMono: MONO_SYSTEM,
        },
    },
];

/** How round the corners are, smallest to largest radius. */
export const RADIUS_PRESETS: Preset[] = [
    {
        id: 'sharp',
        labelKey: 'settings.appearance.preset.radius.sharp',
        tokens: { radiusSm: '0rem', radiusMd: '0.125rem', radiusLg: '0.25rem', radiusXl: '0.375rem', radius2xl: '0.5rem' },
    },
    {
        id: 'balanced',
        labelKey: 'settings.appearance.preset.radius.balanced',
        tokens: { radiusSm: '0.375rem', radiusMd: '0.5rem', radiusLg: '0.75rem', radiusXl: '1rem', radius2xl: '1.25rem' },
    },
    {
        id: 'round',
        labelKey: 'settings.appearance.preset.radius.round',
        tokens: { radiusSm: '0.625rem', radiusMd: '0.875rem', radiusLg: '1.125rem', radiusXl: '1.5rem', radius2xl: '1.75rem' },
    },
];

/** How much a surface is lifted off the page. */
export const SHADOW_PRESETS: Preset[] = [
    {
        id: 'flat',
        labelKey: 'settings.appearance.preset.shadow.flat',
        tokens: {
            shadowSm: '0 1px 1px 0 rgb(28 26 22 / 0.04)',
            shadowMd: '0 1px 2px 0 rgb(28 26 22 / 0.05)',
            shadowLg: '0 2px 6px -2px rgb(28 26 22 / 0.08)',
            shadowPanel: '0 1px 2px rgb(28 26 22 / 0.04), 0 8px 24px -16px rgb(28 26 22 / 0.12)',
            shadowLift: '0 1px 2px rgb(28 26 22 / 0.05), 0 6px 16px -10px rgb(6 40 30 / 0.14)',
        },
    },
    {
        id: 'soft',
        labelKey: 'settings.appearance.preset.shadow.soft',
        tokens: {
            shadowSm: '0 1px 2px 0 rgb(28 26 22 / 0.05)',
            shadowMd: '0 2px 8px -2px rgb(28 26 22 / 0.08), 0 4px 16px -6px rgb(28 26 22 / 0.06)',
            shadowLg: '0 10px 24px -8px rgb(28 26 22 / 0.12), 0 4px 8px -4px rgb(28 26 22 / 0.05)',
            shadowPanel: '0 1px 2px rgb(28 26 22 / 0.04), 0 24px 48px -24px rgb(28 26 22 / 0.18)',
            shadowLift: '0 1px 2px rgb(28 26 22 / 0.05), 0 18px 32px -14px rgb(6 40 30 / 0.22)',
        },
    },
    {
        id: 'deep',
        labelKey: 'settings.appearance.preset.shadow.deep',
        tokens: {
            shadowSm: '0 1px 3px 0 rgb(28 26 22 / 0.08)',
            shadowMd: '0 4px 12px -2px rgb(28 26 22 / 0.12), 0 8px 24px -8px rgb(28 26 22 / 0.1)',
            shadowLg: '0 16px 40px -10px rgb(28 26 22 / 0.18), 0 6px 12px -6px rgb(28 26 22 / 0.08)',
            shadowPanel: '0 2px 4px rgb(28 26 22 / 0.06), 0 32px 64px -24px rgb(28 26 22 / 0.26)',
            shadowLift: '0 2px 4px rgb(28 26 22 / 0.06), 0 26px 44px -16px rgb(6 40 30 / 0.3)',
        },
    },
];

/**
 * Which choice is on screen, or `null` when the tokens match none of them.
 *
 * A school that edited one radius by hand is not using any of the presets, and
 * the screen should say so rather than lighting up the nearest one.
 */
export function activePresetId(presets: Preset[], tokens: Record<string, string>): string | null {
    const match = presets.find((preset) =>
        Object.entries(preset.tokens).every(([key, value]) => tokens[key] === value),
    );

    return match?.id ?? null;
}

/** The three groups, in the order the screen shows them. */
export const TOKEN_PRESET_GROUPS: { id: string; labelKey: CopyKey; presets: Preset[] }[] = [
    { id: 'font', labelKey: 'settings.appearance.preset.font', presets: FONT_PRESETS },
    { id: 'radius', labelKey: 'settings.appearance.preset.radius', presets: RADIUS_PRESETS },
    { id: 'shadow', labelKey: 'settings.appearance.preset.shadow', presets: SHADOW_PRESETS },
];
