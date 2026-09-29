import { useForm, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export type InterfaceTranslations = {
    data: Array<{ id: number; english: string; arabic: string; updated_at: string }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

/**
 * Hand-written overrides for the machine's interface translations.
 *
 * Kept apart from the provider settings it sits under: it is a different job with
 * its own form, its own pagination and its own permission, and it was a hundred
 * lines of JSX wedged into the bottom of a thousand-line page.
 */
export function InterfaceCopyPanel({ translations }: { translations: InterfaceTranslations }) {
    const form = useForm({ english: '', arabic: '' });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/settings/translations/interface-copy', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const remove = (id: number) => {
        router.delete(`/settings/translations/interface-copy/${id}`, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Shared Arabic interface translations</CardTitle>
                <CardDescription>
                    These translations are shared across every school and user. They override automatic
                    translations throughout the system.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-5">
                <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="interface-english">English interface text</Label>
                        <Input
                            id="interface-english"
                            value={form.data.english}
                            onChange={(event) => form.setData('english', event.target.value)}
                            maxLength={300}
                            required
                        />
                        {form.errors.english && <p className="text-xs text-destructive">{form.errors.english}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="interface-arabic">Arabic translation</Label>
                        <textarea
                            id="interface-arabic"
                            dir="rtl"
                            className="input min-h-10 w-full"
                            value={form.data.arabic}
                            onChange={(event) => form.setData('arabic', event.target.value)}
                            maxLength={5000}
                            required
                        />
                        {form.errors.arabic && <p className="text-xs text-destructive">{form.errors.arabic}</p>}
                    </div>
                    <div className="md:col-span-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save shared translation'}
                        </Button>
                    </div>
                </form>

                <div className="divide-y rounded-md border">
                    {translations.data.map((entry) => (
                        <div key={entry.id} className="grid gap-3 p-3 md:grid-cols-[1fr_1fr_auto] md:items-center">
                            <p className="break-words text-sm">{entry.english}</p>
                            <p dir="rtl" className="break-words text-sm text-muted-foreground">
                                {entry.arabic}
                            </p>
                            <div className="flex items-center gap-2 md:justify-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => form.setData({ english: entry.english, arabic: entry.arabic })}
                                >
                                    Edit
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    aria-label={`Delete translation for ${entry.english}`}
                                    onClick={() => remove(entry.id)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    ))}
                    {translations.data.length === 0 && (
                        <p className="p-4 text-sm text-muted-foreground">No shared translations yet.</p>
                    )}
                </div>

                {translations.links.length > 3 && (
                    <nav className="flex flex-wrap gap-2" aria-label="Shared translation pages">
                        {translations.links.map((link) => (
                            <Button
                                key={link.label}
                                type="button"
                                variant={link.active ? 'default' : 'outline'}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })}
                            >
                                {link.label.replace(/&amp;/g, '&')}
                            </Button>
                        ))}
                    </nav>
                )}
            </CardContent>
        </Card>
    );
}
