import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';

export type ThemeMode = 'light' | 'dark' | 'system';
export type ResolvedMode = 'light' | 'dark';

/** The five headline tokens a school can tune per colour mode. */
export type Palette = {
    accent: string;
    background: string;
    surface: string;
    text: string;
    muted: string;
};

export type Palettes = { light: Palette; dark: Palette };

export const DEFAULT_PALETTES: Palettes = {
    light: { accent: '#006c55', background: '#f4f3ee', surface: '#ffffff', text: '#0a0a0a', muted: '#6b6b64' },
    dark: { accent: '#006c55', background: '#070707', surface: '#0b0b0b', text: '#f4f4f1', muted: '#a4a4a8' },
};

/** Which CSS custom properties each headline token drives. */
const TOKEN_VARS: Record<keyof Palette, string[]> = {
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
export function applyPalette(palette: Palette): void {
    const root = document.documentElement;

    (Object.keys(TOKEN_VARS) as (keyof Palette)[]).forEach((token) => {
        const value = palette[token];
        if (!value) return;
        TOKEN_VARS[token].forEach((cssVar) => root.style.setProperty(cssVar, value));
    });

    root.style.setProperty('--color-primary-foreground', getContrastingColor(palette.accent));
    root.style.setProperty('--color-button-primary-foreground', getContrastingColor(palette.accent));
}

/** Single entry point that flips the mode class and applies the matching palette. */
export function applyTheme(mode: ThemeMode, palettes: Palettes): void {
    const root = document.documentElement;
    const resolved = resolveMode(mode);

    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.theme = resolved;

    applyPalette(palettes[resolved]);
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

/** Persists the preference server-side so it follows the user between devices. */
export function persistMode(mode: ThemeMode): void {
    storeMode(mode);
    router.post('/settings/theme/mode', { mode }, { preserveState: true, preserveScroll: true, preserveUrl: true });
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
};

export function ThemeProvider({ children, initialMode, palettes }: ThemeProviderProps) {
    const [mode, setModeState] = useState<ThemeMode>(() => readStoredMode() ?? initialMode);
    const [resolved, setResolved] = useState<ResolvedMode>(() => resolveMode(readStoredMode() ?? initialMode));

    const activePalettes = useMemo<Palettes>(() => palettes ?? DEFAULT_PALETTES, [palettes]);

    // Apply on mount and whenever the mode or the school palettes change.
    useEffect(() => {
        applyTheme(mode, activePalettes);
        setResolved(resolveMode(mode));
    }, [mode, activePalettes]);

    // Follow the OS when the preference is "system".
    useEffect(() => {
        if (mode !== 'system') return;
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const onChange = () => {
            applyTheme('system', activePalettes);
            setResolved(resolveMode('system'));
        };
        media.addEventListener('change', onChange);
        return () => media.removeEventListener('change', onChange);
    }, [mode, activePalettes]);

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

export function useThemeMode(): ThemeContextValue {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useThemeMode must be used inside a ThemeProvider');
    }
    return context;
}
