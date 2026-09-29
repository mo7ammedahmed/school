import { useMemo, useState, type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { postJson, useTranslationSweep } from '@/lib/translation-sweep';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Languages, PlugZap, RefreshCw, Sparkles, Wand2 } from 'lucide-react';
import { InterfaceCopyPanel, type InterfaceTranslations } from '@/components/settings/interface-copy-panel';

type Provider = {
    value: string;
    label: string;
    driver: string;
    models: string[];
    default_model: string;
    default_base_url: string;
    requires_base_url: boolean;
    key_placeholder: string;
    help_url: string;
};

type ProviderKeyState = { has_school_key: boolean; has_env_key: boolean };

type Settings = {
    provider: string;
    provider_label: string;
    model: string;
    base_url: string;
    auto_translate: boolean;
    source_locale: string;
    target_locale: string;
    has_api_key: boolean;
    has_own_key: boolean;
    configured: boolean;
    key_source: 'school' | 'environment' | 'none';
    providers_with_keys: Record<string, ProviderKeyState>;
};

type Props = {
    settings: Settings;
    providers: Provider[];
    locales: { value: string; label: string }[];
    endpoint: string;
    envKeyConfigured: boolean;
    canManageInterfaceCopy: boolean;
    interfaceTranslations: InterfaceTranslations | null;
};

export default function TranslationSettingsPage({
    settings,
    providers,
    locales,
    endpoint,
    envKeyConfigured,
    canManageInterfaceCopy,
    interfaceTranslations,
}: Props) {
    const form = useForm({
        provider: settings.provider,
        model: settings.model,
        base_url: settings.base_url,
        auto_translate: settings.auto_translate,
        source_locale: settings.source_locale,
        target_locale: settings.target_locale,
        api_key: '',
        clear_api_key: false,
    });
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState<{ ok: boolean; message: string } | null>(null);
    const [loadingModels, setLoadingModels] = useState(false);
    const [liveModels, setLiveModels] = useState<string[] | null>(null);
    const [modelsError, setModelsError] = useState<string | null>(null);
    // The batched sweep and its report live in a hook: the run's clock, its
    // abort handling and the wording of its report are not layout.
    const { backfilling, report, progress, run: runBackfill } = useTranslationSweep();

    const activeProvider = providers.find((p) => p.value === form.data.provider) ?? providers[0];
    const keyState = settings.providers_with_keys?.[form.data.provider] ?? {
        has_school_key: false,
        has_env_key: false,
    };

    const modelOptions = useMemo(() => {
        const merged = [...activeProvider.models, ...(liveModels ?? [])];

        return Array.from(new Set(merged.filter(Boolean)));
    }, [activeProvider.models, liveModels]);

    // Mirrors the server-side endpoint builder so the preview tracks edits.
    const endpointPreview = useMemo(() => {
        const base = (form.data.base_url || activeProvider.default_base_url || '').replace(/\/+$/, '');

        if (!base) return 'Set a base URL to see the endpoint.';

        switch (activeProvider.driver) {
            case 'anthropic':
                return `${base}/v1/messages`;
            case 'gemini':
                return `${base}/v1beta/models/${form.data.model || '{model}'}:generateContent`;
            default:
                return `${base}/chat/completions`;
        }
    }, [activeProvider, form.data.base_url, form.data.model]);

    const changeProvider = (value: string) => {
        const next = providers.find((p) => p.value === value);

        setLiveModels(null);
        setModelsError(null);
        setTestResult(null);

        form.setData({
            ...form.data,
            provider: value,
            // Each provider has its own model namespace, so start from its default.
            model: next?.default_model || '',
            base_url: next?.requires_base_url ? form.data.base_url : '',
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/settings/translations', { preserveScroll: true });
    };

    const testConnection = async () => {
        setTesting(true);
        setTestResult(null);

        try {
            const { ok, payload } = await postJson('/settings/translations/test');
            setTestResult({
                ok,
                message: String(payload.message ?? 'No response from the translation service.'),
            });
        } catch {
            setTestResult({ ok: false, message: 'Could not reach the server.' });
        } finally {
            setTesting(false);
        }
    };

    const loadModels = async () => {
        setLoadingModels(true);
        setModelsError(null);

        try {
            const { ok, payload } = await postJson('/settings/translations/models');

            if (!ok) {
                setModelsError(String(payload.message ?? 'Could not load models.'));
                return;
            }

            const models = Array.isArray(payload.models) ? (payload.models as string[]) : [];
            setLiveModels(models);
            setModelsError(models.length === 0 ? 'The provider returned no models.' : null);
        } catch {
            setModelsError('Could not reach the server.');
        } finally {
            setLoadingModels(false);
        }
    };

    const keySourceLabel =
        settings.key_source === 'school'
            ? 'School key'
            : settings.key_source === 'environment'
              ? 'Deployment key'
              : 'Not configured';

    return (
        <AppShell
            title="Translations"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Translations' },
            ]}
        >
            <PageHeader
                title="Translations"
                description="Fill in the Arabic and English versions of your content automatically, using any AI provider."
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <form onSubmit={submit} className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Sparkles className="h-4 w-4" />
                                Automatic translation
                            </CardTitle>
                            <CardDescription>
                                When you save a name, subject, room or page in one language, the other language
                                is translated and stored for you.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <label className="flex cursor-pointer items-start gap-3">
                                <Checkbox
                                    checked={form.data.auto_translate}
                                    onChange={(event) => form.setData('auto_translate', event.target.checked)}
                                    className="mt-0.5"
                                />
                                <span className="text-sm">
                                    <span className="font-medium">Fill missing translations on save</span>
                                    <span className="block text-muted-foreground">
                                        Only empty fields are filled — anything you type yourself is never
                                        overwritten.
                                    </span>
                                </span>
                            </label>

                            <div className="grid gap-5 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="source_locale">Staff enter content in</Label>
                                    <Select
                                        id="source_locale"
                                        value={form.data.source_locale}
                                        onChange={(event) => form.setData('source_locale', event.target.value)}
                                    >
                                        {locales.map((locale) => (
                                            <option key={locale.value} value={locale.value}>
                                                {locale.label}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="target_locale">Translate into</Label>
                                    <Select
                                        id="target_locale"
                                        value={form.data.target_locale}
                                        onChange={(event) => form.setData('target_locale', event.target.value)}
                                    >
                                        {locales.map((locale) => (
                                            <option key={locale.value} value={locale.value}>
                                                {locale.label}
                                            </option>
                                        ))}
                                    </Select>
                                    {form.errors.target_locale && (
                                        <p className="mt-1 text-xs text-destructive">{form.errors.target_locale}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <PlugZap className="h-4 w-4" />
                                Provider
                            </CardTitle>
                            <CardDescription>
                                Pick any AI service — the key is encrypted and stored per school.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div>
                                <Label htmlFor="provider">AI provider</Label>
                                <Select
                                    id="provider"
                                    value={form.data.provider}
                                    onChange={(event) => changeProvider(event.target.value)}
                                >
                                    {providers.map((provider) => (
                                        <option key={provider.value} value={provider.value}>
                                            {provider.label}
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            {activeProvider.requires_base_url && (
                                <div>
                                    <Label htmlFor="base_url">Base URL</Label>
                                    <Input
                                        id="base_url"
                                        value={form.data.base_url}
                                        onChange={(event) => form.setData('base_url', event.target.value)}
                                        placeholder="https://my-endpoint.example.com/v1"
                                        spellCheck={false}
                                    />
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Any OpenAI-compatible endpoint (Azure OpenAI, OpenRouter, a local vLLM…).
                                    </p>
                                    {form.errors.base_url && (
                                        <p className="mt-1 text-xs text-destructive">{form.errors.base_url}</p>
                                    )}
                                </div>
                            )}

                            <div>
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="model">Model</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => void loadModels()}
                                        disabled={loadingModels}
                                    >
                                        <RefreshCw className={`mr-2 h-3.5 w-3.5 ${loadingModels ? 'animate-spin' : ''}`} />
                                        {loadingModels ? 'Loading…' : 'Load models'}
                                    </Button>
                                </div>
                                <Input
                                    id="model"
                                    list="translation-models"
                                    value={form.data.model}
                                    onChange={(event) => form.setData('model', event.target.value)}
                                    placeholder="Type any model id"
                                    spellCheck={false}
                                />
                                <datalist id="translation-models">
                                    {modelOptions.map((model) => (
                                        <option key={model} value={model} />
                                    ))}
                                </datalist>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Providers retire model ids, so you can type any id or load the live list.
                                </p>
                                {modelsError && <p className="mt-1 text-xs text-destructive">{modelsError}</p>}
                            </div>

                            <div>
                                <Label htmlFor="api_key">API key</Label>
                                <Input
                                    id="api_key"
                                    type="password"
                                    autoComplete="new-password"
                                    value={form.data.api_key}
                                    onChange={(event) => form.setData('api_key', event.target.value)}
                                    placeholder={
                                        keyState.has_school_key
                                            ? '•••••••• stored'
                                            : keyState.has_env_key
                                              ? 'Using the deployment key'
                                              : activeProvider.key_placeholder
                                    }
                                />
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {activeProvider.help_url ? (
                                        <>
                                            Get a key from{' '}
                                            <a
                                                href={activeProvider.help_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="underline"
                                            >
                                                {new URL(activeProvider.help_url).host}
                                            </a>
                                            .{' '}
                                        </>
                                    ) : null}
                                    It is encrypted at rest and never sent back to the browser. Leave blank to
                                    keep the stored key for this provider.
                                </p>
                                {keyState.has_school_key && (
                                    <label className="mt-2 flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                                        <Checkbox
                                            checked={form.data.clear_api_key}
                                            onChange={(event) =>
                                                form.setData('clear_api_key', event.target.checked)
                                            }
                                        />
                                        Remove the stored key for {activeProvider.label}
                                    </label>
                                )}
                            </div>

                            <div className="rounded-lg border border-border/60 bg-muted/30 p-4 text-xs text-muted-foreground">
                                <p className="font-medium text-foreground">Endpoint</p>
                                <code className="mt-1 block break-all">{endpointPreview}</code>
                                {endpointPreview !== endpoint && (
                                    <p className="mt-1">Saved endpoint: {endpoint}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </Button>
                        {form.recentlySuccessful && <span className="text-sm text-muted-foreground">Saved.</span>}
                    </div>
                </form>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Translation</span>
                                <Badge variant={settings.configured ? 'success' : 'secondary'}>
                                    {settings.configured ? 'ready' : 'not configured'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Provider</span>
                                <span className="font-medium">{settings.provider_label}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Key source</span>
                                <span className="font-medium">{keySourceLabel}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">On save</span>
                                <span className="font-medium">
                                    {settings.auto_translate ? (settings.configured ? 'automatic' : 'off') : 'manual'}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="mt-2 w-full justify-start"
                                onClick={() => void testConnection()}
                                disabled={testing}
                            >
                                <PlugZap className="mr-2 h-4 w-4" />
                                {testing ? 'Testing…' : 'Test connection'}
                            </Button>
                            {testResult && (
                                <p
                                    className={
                                        testResult.ok
                                            ? 'text-xs text-success'
                                            : 'text-xs text-destructive'
                                    }
                                >
                                    {testResult.message}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Languages className="h-4 w-4" />
                                Where it applies
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm text-muted-foreground">
                            <p>Academic years, semesters, grade levels, sections, subjects, rooms and period names.</p>
                            <p>Content page titles and the school name.</p>
                            <p>
                                Translations save immediately on existing records; new records save them with
                                the form.
                            </p>
                            {envKeyConfigured && (
                                <p className="pt-2 text-xs">
                                    A deployment-wide key is configured for {settings.provider_label}; a key saved
                                    here overrides it.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Wand2 className="h-4 w-4" />
                                Translate what is missing
                            </CardTitle>
                            <CardDescription>
                                Checks every Arabic and English field in the system and translates the empty
                                side, so older records catch up with the ones created since. Runs in small
                                batches, so you can watch the progress and stop at any time.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p className="text-sm text-muted-foreground">
                                English writes Arabic and Arabic writes English — whichever side is empty is the
                                one that gets filled. Anything you typed yourself is left untouched.
                            </p>

                            <Button
                                type="button"
                                className="w-full justify-start"
                                onClick={() => void runBackfill()}
                                disabled={!settings.configured}
                            >
                                {backfilling ? (
                                    <>
                                        <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                                        Stop translating
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="mr-2 h-4 w-4" />
                                        Translate everything that is missing
                                    </>
                                )}
                            </Button>

                            {!settings.configured && (
                                <p className="text-xs text-muted-foreground">
                                    Add an API key first — translation is turned off until then.
                                </p>
                            )}

                            {progress && (backfilling || progress.total > 0) && (
                                <div className="space-y-2" aria-live="polite">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground">
                                            {backfilling ? 'Translating…' : 'Translated'}
                                        </span>
                                        <span className="font-medium tabular-nums text-foreground">
                                            {Math.min(progress.done, progress.total)} / {progress.total}
                                        </span>
                                    </div>
                                    <div
                                        className="h-1.5 w-full overflow-hidden rounded-full bg-border/60"
                                        role="progressbar"
                                        aria-valuemin={0}
                                        aria-valuemax={progress.total}
                                        aria-valuenow={Math.min(progress.done, progress.total)}
                                    >
                                        <div
                                            className="h-full rounded-full bg-primary transition-[width] duration-300"
                                            style={{
                                                width: `${
                                                    progress.total > 0
                                                        ? Math.min(
                                                              100,
                                                              Math.round(
                                                                  (Math.min(progress.done, progress.total) /
                                                                      progress.total) *
                                                                      100,
                                                              ),
                                                          )
                                                        : 0
                                                }%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            )}

                            {report && (
                                <div className="space-y-2 rounded-lg border border-border/60 bg-muted/30 p-3 text-xs">
                                    <p className={report.ok ? 'text-success' : 'text-destructive'}>
                                        {report.message}
                                    </p>

                                    {report.error && <p className="text-destructive">{report.error}</p>}

                                    {report.added.length > 0 && (
                                        <div className="border-t border-border/60 pt-2">
                                            <p className="mb-1 font-medium text-foreground">
                                                Added in this run
                                            </p>
                                            <ul className="space-y-1">
                                                {report.added
                                                    .slice()
                                                    .sort(
                                                        (a, b) =>
                                                            b.intoArabic +
                                                            b.intoEnglish -
                                                            (a.intoArabic + a.intoEnglish),
                                                    )
                                                    .slice(0, 12)
                                                    .map((entry) => (
                                                        <li
                                                            key={entry.label}
                                                            className="flex items-center justify-between gap-2"
                                                        >
                                                            <span className="truncate text-muted-foreground">
                                                                {entry.label}
                                                            </span>
                                                            {/* Spelled out rather than arrowed: arrows are mirrored in an RTL layout. */}
                                                            <span className="shrink-0 tabular-nums text-foreground">
                                                                {entry.intoArabic > 0 &&
                                                                    `${entry.intoArabic} into Arabic`}
                                                                {entry.intoArabic > 0 &&
                                                                    entry.intoEnglish > 0 &&
                                                                    ' · '}
                                                                {entry.intoEnglish > 0 &&
                                                                    `${entry.intoEnglish} into English`}
                                                            </span>
                                                        </li>
                                                    ))}
                                            </ul>
                                        </div>
                                    )}

                                    {report.targets.some((target) => target.remaining > 0) && (
                                        <div className="border-t border-border/60 pt-2">
                                            <p className="mb-1 font-medium text-foreground">
                                                Still empty
                                            </p>
                                            <ul className="space-y-1">
                                                {report.targets
                                                    .filter((target) => target.remaining > 0)
                                                    .sort((a, b) => b.remaining - a.remaining)
                                                    .slice(0, 12)
                                                    .map((target) => (
                                                        <li
                                                            key={target.label}
                                                            className="flex items-center justify-between gap-2"
                                                        >
                                                            <span className="truncate text-muted-foreground">
                                                                {target.label}
                                                            </span>
                                                            <span className="shrink-0 tabular-nums text-foreground">
                                                                {target.remaining} left
                                                            </span>
                                                        </li>
                                                    ))}
                                            </ul>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
            {canManageInterfaceCopy && interfaceTranslations && (
                <div className="mt-6">
                    <InterfaceCopyPanel translations={interfaceTranslations} />
                </div>
            )}
        </AppShell>
    );
}
