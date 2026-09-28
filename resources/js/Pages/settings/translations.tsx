import { useMemo, useRef, useState, type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
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
};

type BackfillTotals = {
    scanned: number;
    missing: number;
    translated: number;
    failed: number;
    remaining: number;
    en_to_ar: number;
    ar_to_en: number;
};

type BackfillTarget = {
    label: string;
    scanned: number;
    missing: number;
    translated: number;
    failed: number;
    /** What the server says is still empty after the scan it just made. */
    remaining: number;
    en_to_ar: number;
    ar_to_en: number;
};

/** One table's contribution to the run, summed over every batch. */
type BackfillAdded = {
    label: string;
    intoArabic: number;
    intoEnglish: number;
};

type BackfillReport = {
    ok: boolean;
    message: string;
    error: string | null;
    /** The last scan, so "left" is measured after the run rather than before it. */
    targets: BackfillTarget[];
    /** What the run actually added, per table, in both directions. */
    added: BackfillAdded[];
};

const EMPTY_TOTALS: BackfillTotals = {
    scanned: 0,
    missing: 0,
    translated: 0,
    failed: 0,
    remaining: 0,
    en_to_ar: 0,
    ar_to_en: 0,
};

/**
 * How many values one request translates.
 *
 * Every value is its own provider call, so a whole-school sweep in a single
 * request could run for many minutes and left the screen stuck on
 * "Translating…". The run is therefore walked in small batches, each one short
 * enough to finish comfortably, with the progress reported between them.
 */
const BACKFILL_BATCH = 10;

/** POST helper for the JSON actions on this screen. */
async function postJson(
    url: string,
    body?: Record<string, unknown>,
    signal?: AbortSignal,
): Promise<{ ok: boolean; status: number; payload: Record<string, unknown> }> {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
        },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
        signal,
    });

    const payload = (await response.json().catch(() => ({}))) as Record<string, unknown>;

    return { ok: response.ok, status: response.status, payload };
}

/** Totals as the server reports them, with every key present. */
function totalsFrom(payload: Record<string, unknown>): BackfillTotals {
    return { ...EMPTY_TOTALS, ...((payload.totals ?? {}) as Partial<BackfillTotals>) };
}

function targetsFrom(payload: Record<string, unknown>): BackfillTarget[] {
    return (Array.isArray(payload.targets) ? (payload.targets as BackfillTarget[]) : []).map((target) => ({
        ...target,
        // Older payloads have no `remaining`; the difference is the same number.
        remaining: target.remaining ?? Math.max(0, target.missing - target.translated - target.failed),
    }));
}

/** Adds one payload's per-table counts onto the running totals for the run. */
function accumulate(
    added: Map<string, BackfillAdded>,
    targets: BackfillTarget[],
): void {
    for (const target of targets) {
        const entry = added.get(target.label) ?? { label: target.label, intoArabic: 0, intoEnglish: 0 };

        entry.intoArabic += target.en_to_ar;
        entry.intoEnglish += target.ar_to_en;
        added.set(target.label, entry);
    }
}

export default function TranslationSettingsPage({
    settings,
    providers,
    locales,
    endpoint,
    envKeyConfigured,
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
    const [backfilling, setBackfilling] = useState(false);
    const [report, setReport] = useState<BackfillReport | null>(null);
    const [progress, setProgress] = useState<{ done: number; total: number } | null>(null);
    const abortBackfill = useRef<AbortController | null>(null);
    // A ref, not state: the guard has to be correct on the very first click,
    // before React has re-rendered the button into its "stop" shape.
    const running = useRef(false);

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

    const stopBackfill = () => {
        abortBackfill.current?.abort();
    };

    /**
     * Walks every bilingual table and fills whichever side is empty — Arabic
     * from English and English from Arabic — in batches, reporting progress as
     * it goes so a long sweep never looks frozen.
     */
    const runBackfill = async () => {
        // While a sweep is in flight the button becomes "stop" rather than
        // starting a second run that would fight the first over the same rows.
        if (running.current) {
            stopBackfill();
            return;
        }

        running.current = true;

        const controller = new AbortController();
        abortBackfill.current = controller;

        setBackfilling(true);
        setReport(null);
        setProgress(null);

        const url = '/settings/translations/backfill';
        let translated = 0;
        let failed = 0;
        let enToAr = 0;
        let arToEn = 0;
        let backlog = 0;
        let targets: BackfillTarget[] = [];
        let serverError: string | null = null;
        // Every request re-scans from the top, so the per-table numbers in a
        // single response only describe that batch. Summing them over the run is
        // what lets the report say what the run added, per table and direction.
        const added = new Map<string, BackfillAdded>();
        const addedList = (): BackfillAdded[] =>
            [...added.values()].filter((entry) => entry.intoArabic + entry.intoEnglish > 0);

        try {
            // A scan costs nothing: it counts what is missing without calling
            // the provider, so progress can be measured against a real total.
            const scan = await postJson(url, { limit: 0 }, controller.signal);

            if (!scan.ok) {
                setReport({
                    ok: false,
                    message: String(scan.payload.message ?? 'Could not scan for missing translations.'),
                    error: typeof scan.payload.error === 'string' ? scan.payload.error : null,
                    targets: [],
                    added: [],
                });
                return;
            }

            backlog = totalsFrom(scan.payload).missing;
            targets = targetsFrom(scan.payload);
            setProgress({ done: 0, total: backlog });

            // Each batch keeps going while it is making progress; a batch that
            // translates nothing means the provider is refusing, so stop rather
            // than loop forever on the same value. The ceiling is generous
            // because the server also stops on its own clock, which can return
            // fewer values than were asked for.
            const attempts = backlog + 20;

            for (let round = 0; round < attempts; round++) {
                if (backlog - translated - failed <= 0) break;

                const step = await postJson(url, { limit: BACKFILL_BATCH }, controller.signal);
                const totals = totalsFrom(step.payload);

                if (!step.ok) {
                    serverError = String(step.payload.error ?? step.payload.message ?? 'The last batch failed.');
                    targets = targetsFrom(step.payload);
                    failed += totals.failed;
                    break;
                }

                translated += totals.translated;
                failed += totals.failed;
                enToAr += totals.en_to_ar;
                arToEn += totals.ar_to_en;
                targets = targetsFrom(step.payload);
                accumulate(added, targets);
                serverError = typeof step.payload.error === 'string' ? step.payload.error : null;

                setProgress({ done: translated, total: backlog });

                if (step.payload.done === true) break;

                // Nothing translated and something failed: the provider is
                // unhappy, so hand the decision back to the operator.
                if (totals.translated === 0) {
                    serverError ??= 'The translation service did not answer for the values it tried. Check the key, model and provider.';
                    break;
                }
            }

            const remaining = Math.max(0, backlog - translated - failed);
            const directions: string[] = [];
            if (enToAr > 0) directions.push(`${enToAr} into Arabic`);
            if (arToEn > 0) directions.push(`${arToEn} into English`);
            const directionText = directions.length > 0 ? ` (${directions.join(', ')})` : '';

            setProgress({ done: translated, total: backlog });

            setReport({
                ok: serverError === null && remaining === 0,
                message:
                    backlog === 0
                        ? 'Nothing was missing — every record already has both Arabic and English.'
                        : serverError !== null
                          ? `Translated ${translated} of ${backlog}${directionText} before stopping.`
                          : remaining === 0
                            ? `Done — translated ${translated} of ${backlog} missing values${directionText}.`
                            : `Translated ${translated} of ${backlog}${directionText}. ${remaining} left — press again to continue.`,
                error: serverError,
                targets,
                added: addedList(),
            });
        } catch (error) {
            const aborted = error instanceof DOMException && error.name === 'AbortError';
            const remaining = Math.max(0, backlog - translated - failed);

            setReport({
                ok: false,
                message: aborted
                    ? `Stopped. Translated ${translated} of ${backlog}; ${remaining} left.`
                    : 'Could not reach the server. Nothing else was changed.',
                error: aborted ? null : 'The request to the translation service did not complete.',
                targets,
                added: addedList(),
            });
        } finally {
            running.current = false;
            abortBackfill.current = null;
            setBackfilling(false);
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
                                Every bilingual field also has a translate button, so you can translate one field
                                without saving.
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
        </AppShell>
    );
}
