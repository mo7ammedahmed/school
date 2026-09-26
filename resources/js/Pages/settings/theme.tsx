import { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Moon, Sun, RotateCcw } from 'lucide-react';
import { DEFAULT_PALETTES, getContrastingColor, useThemeMode, type Palette } from '@/lib/theme';
import { cn } from '@/lib/utils';

type Mode = 'light' | 'dark';
type TokenKey = keyof Palette;

/** A token value plus its "Solid" flag (a tint renders semi-transparent). */
type Token = { value: string; solid: boolean };
type ModeState = Record<TokenKey, Token>;
type ModesState = Record<Mode, ModeState>;

const TOKENS: { key: TokenKey; label: string; hint: string }[] = [
    { key: 'accent', label: 'Accent', hint: 'Buttons, links and the active nav item.' },
    { key: 'background', label: 'Background', hint: 'The page canvas behind every panel.' },
    { key: 'surface', label: 'Surface', hint: 'Cards, popovers, inputs and table rows.' },
    { key: 'text', label: 'Text', hint: 'Headings and body copy.' },
    { key: 'muted', label: 'Muted text', hint: 'Captions, hints and secondary labels.' },
];

const TOKEN_ORDER: TokenKey[] = ['accent', 'background', 'surface', 'text', 'muted'];

type IncomingModes = Record<Mode, Record<string, string | boolean>>;

function toState(modes?: IncomingModes | null): ModesState {
    const build = (mode: Mode): ModeState => {
        const source = modes?.[mode] ?? {};
        return TOKEN_ORDER.reduce((acc, key) => {
            acc[key] = {
                value: String(source[key] ?? DEFAULT_PALETTES[mode][key]),
                solid: source[`${key}_solid`] !== false,
            };
            return acc;
        }, {} as ModeState);
    };

    return { light: build('light'), dark: build('dark') };
}

function flatten(state: ModesState): Record<Mode, Record<string, string | boolean>> {
    const build = (mode: Mode) =>
        TOKEN_ORDER.reduce<Record<string, string | boolean>>((acc, key) => {
            acc[key] = state[mode][key].value;
            acc[`${key}_solid`] = state[mode][key].solid;
            return acc;
        }, {});

    return { light: build('light'), dark: build('dark') };
}

/** A tint renders at 55% opacity so "Solid" visibly changes the preview. */
function tokenColor(token: Token): string {
    return token.solid ? token.value : `color-mix(in srgb, ${token.value} 55%, transparent)`;
}

type ThemeSettingsProps = {
    themeModes: IncomingModes;
    mode: 'light' | 'dark' | 'system';
    school: { id: number; name: string };
};

export default function ThemeSettings({ themeModes, school }: ThemeSettingsProps) {
    const { palettes, resolved } = useThemeMode();
    const [state, setState] = useState<ModesState>(() => toState(themeModes));

    const form = useForm<{ light: Record<string, string | boolean>; dark: Record<string, string | boolean> }>({
        light: flatten(state).light,
        dark: flatten(state).dark,
    });

    // Re-sync when the server sends a new palette (e.g. after a save).
    useEffect(() => {
        setState(toState(themeModes));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [themeModes]);

    const activePalette = useMemo(
        () => (resolved === 'dark' ? palettes.dark : palettes.light),
        [palettes, resolved]
    );

    const update = (mode: Mode, key: TokenKey, patch: Partial<Token>) => {
        setState((prev) => ({
            ...prev,
            [mode]: { ...prev[mode], [key]: { ...prev[mode][key], ...patch } },
        }));
    };

    const submit = () => {
        form.transform(() => flatten(state));
        form.post('/settings/theme', { preserveScroll: true });
    };

    const reset = () => {
        setState(toState(null));
        form.setData(flatten(toState(null)));
    };

    return (
        <AppShell
            title="Theme"
            breadcrumbs={[{ label: 'Settings', href: '/settings/school' }, { label: 'Theme' }]}
        >
            <PageHeader
                title="Theme"
                description={`Control the light and dark palettes for ${school.name} and pick the mode you prefer.`}
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" onClick={reset} disabled={form.processing}>
                            <RotateCcw className="me-2 h-4 w-4" />
                            Reset to defaults
                        </Button>
                        <Button onClick={submit} disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save theme'}
                        </Button>
                    </div>
                }
            />

            {form.recentlySuccessful && (
                <p className="mt-4 rounded-lg border border-success/30 bg-success/10 px-3 py-2 text-sm text-success">
                    Theme colours saved. Every page now uses the {activePalette ? resolved : 'active'} palette.
                </p>
            )}

            <div className="mt-6 grid gap-6 xl:grid-cols-2">
                {(['dark', 'light'] as Mode[]).map((mode) => (
                    <ModeCard
                        key={mode}
                        mode={mode}
                        state={state[mode]}
                        onChange={(key, patch) => update(mode, key, patch)}
                    />
                ))}
            </div>
        </AppShell>
    );
}

function ModeCard({
    mode,
    state,
    onChange,
}: {
    mode: Mode;
    state: ModeState;
    onChange: (key: TokenKey, patch: Partial<Token>) => void;
}) {
    const isDark = mode === 'dark';
    const Icon = isDark ? Moon : Sun;

    return (
        <Card className="h-fit">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Icon className="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                    {isDark ? 'Dark mode' : 'Light mode'}
                </CardTitle>
                <CardDescription>
                    {isDark
                        ? 'Colors used when a visitor chooses the dark appearance.'
                        : 'Colors used when a visitor chooses the light appearance.'}
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-5">
                <div className="grid gap-4 sm:grid-cols-2">
                    {TOKENS.map(({ key, label, hint }) => {
                        const token = state[key];
                        return (
                            <div key={key} className="space-y-1.5">
                                <div className="flex items-baseline justify-between gap-2">
                                    <label
                                        htmlFor={`${mode}-${key}`}
                                        className="text-sm font-medium text-foreground"
                                    >
                                        {label}
                                    </label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <label
                                        htmlFor={`${mode}-${key}-solid`}
                                        className="flex cursor-pointer items-center gap-1.5 rounded-md border border-input px-2 py-1.5 text-xs text-muted-foreground"
                                        title="Render this token as a solid colour"
                                    >
                                        <input
                                            id={`${mode}-${key}-solid`}
                                            type="checkbox"
                                            className="h-3.5 w-3.5 accent-[var(--color-primary)]"
                                            checked={token.solid}
                                            onChange={(event) => onChange(key, { solid: event.target.checked })}
                                        />
                                        Solid
                                    </label>

                                    <input
                                        type="color"
                                        aria-label={`${label} swatch`}
                                        value={token.value}
                                        onChange={(event) => onChange(key, { value: event.target.value })}
                                        className="h-8 w-9 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-0.5"
                                    />

                                    <Input
                                        id={`${mode}-${key}`}
                                        value={token.value}
                                        onChange={(event) => onChange(key, { value: event.target.value })}
                                        className="h-8 font-mono text-xs"
                                        spellCheck={false}
                                    />
                                </div>

                                <p className="text-xs text-muted-foreground">{hint}</p>
                            </div>
                        );
                    })}
                </div>

                <div
                    className="rounded-xl border p-5"
                    style={{
                        backgroundColor: state.background.value,
                        borderColor: tokenColor(state.text),
                        color: state.text.value,
                    }}
                >
                    <p
                        className="text-[0.7rem] font-semibold uppercase tracking-[0.18em]"
                        style={{ color: tokenColor(state.muted) }}
                    >
                        {isDark ? 'Dark preview' : 'Light preview'}
                    </p>
                    <div className="mt-3 flex items-center justify-between gap-4">
                        <p className="text-lg font-semibold" style={{ color: state.text.value }}>
                            {isDark ? 'Your portfolio, your atmosphere.' : 'Changes publish when you save.'}
                        </p>
                        <span
                            className="flex size-9 shrink-0 items-center justify-center rounded-full"
                            style={{
                                backgroundColor: tokenColor(state.accent),
                                color: getContrastingColor(state.accent.value),
                            }}
                        >
                            <span className="size-2.5 rounded-full" style={{ backgroundColor: 'currentColor' }} />
                        </span>
                    </div>
                    <div
                        className={cn('mt-4 rounded-lg border p-3 text-xs')}
                        style={{
                            backgroundColor: tokenColor(state.surface),
                            borderColor: tokenColor(state.text),
                            color: tokenColor(state.muted),
                        }}
                    >
                        Surface · muted copy on the card background.
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
