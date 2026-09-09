import { type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell, { NAV_GROUPS } from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

type LabelRow = { en: string; ar: string };
type Labels = Record<string, LabelRow>;

type NavigationSettingsProps = {
    labels: Record<string, { en?: string | null; ar?: string | null }>;
};

export default function NavigationSettings({ labels }: NavigationSettingsProps) {
    const { locale } = useLocale();
    const ar = locale === 'ar';

    const { data, setData, post, processing, recentlySuccessful, errors } = useForm<{ labels: Labels }>({
        labels: Object.fromEntries(
            NAV_GROUPS.flatMap((group) => [
                ...(group.labelKey ? [group.labelKey] : []),
                ...group.items.map((item) => item.key),
            ]).map((key) => [
                key,
                { en: labels[key]?.en ?? '', ar: labels[key]?.ar ?? '' },
            ])
        ),
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/settings/navigation');
    };

    return (
        <AppShell
            title="Navigation Labels"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Navigation Labels' },
            ]}
        >
            <PageHeader
                title={ar ? 'تسميات التنقل' : 'Navigation Labels'}
                description={
                    ar
                        ? 'تحكم في أسماء عناصر القائمة الجانبية بالعربية والإنجليزية.'
                        : 'Customise the sidebar menu names in Arabic and English.'
                }
            />

            <form onSubmit={submit} className="mt-6 space-y-6">
                {NAV_GROUPS.map((group, gi) => {
                    const labelKey = group.labelKey;
                    return (
                    <Card key={gi}>
                        <CardHeader>
                            <CardTitle>
                                {labelKey
                                    ? t(locale, labelKey)
                                    : ar ? 'الرئيسية' : 'Main'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {labelKey && (
                                <div className="grid gap-3 md:grid-cols-[14rem_1fr_1fr] md:items-center">
                                    <div>
                                        <Label>{ar ? 'عنوان المجموعة' : 'Group label'}</Label>
                                        <p className="mt-0.5 text-xs text-muted-foreground">{labelKey}</p>
                                    </div>
                                    <Input
                                        value={data.labels[labelKey]?.en ?? ''}
                                        onChange={(e) =>
                                            setData('labels', {
                                                ...data.labels,
                                                [labelKey]: { ...data.labels[labelKey], en: e.target.value },
                                            })
                                        }
                                        placeholder="English"
                                    />
                                    <Input
                                        value={data.labels[labelKey]?.ar ?? ''}
                                        onChange={(e) =>
                                            setData('labels', {
                                                ...data.labels,
                                                [labelKey]: { ...data.labels[labelKey], ar: e.target.value },
                                            })
                                        }
                                        placeholder="العربية"
                                        dir="rtl"
                                    />
                                </div>
                            )}
                            {group.items.map((item) => (
                                <div key={item.key} className="grid gap-3 md:grid-cols-[14rem_1fr_1fr] md:items-center">
                                    <div>
                                        <Label>{t(locale, item.key)}</Label>
                                        <p className="mt-0.5 text-xs text-muted-foreground">{item.key}</p>
                                    </div>
                                    <Input
                                        value={data.labels[item.key]?.en ?? ''}
                                        onChange={(e) =>
                                            setData('labels', {
                                                ...data.labels,
                                                [item.key]: { ...data.labels[item.key], en: e.target.value },
                                            })
                                        }
                                        placeholder="English"
                                    />
                                    <Input
                                        value={data.labels[item.key]?.ar ?? ''}
                                        onChange={(e) =>
                                            setData('labels', {
                                                ...data.labels,
                                                [item.key]: { ...data.labels[item.key], ar: e.target.value },
                                            })
                                        }
                                        placeholder="العربية"
                                        dir="rtl"
                                    />
                                </div>
                            ))}
                            {errors[`labels.${gi}`] && <p className="text-sm text-destructive">{errors[`labels.${gi}`]}</p>}
                        </CardContent>
                    </Card>
                    );
                })}

                <div className="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                    <Button type="submit" disabled={processing}>
                        {processing ? (ar ? 'جاري الحفظ...' : 'Saving...') : ar ? 'حفظ التسميات' : 'Save labels'}
                    </Button>
                    {recentlySuccessful && (
                        <p className="text-sm text-success">{ar ? 'تم حفظ التسميات.' : 'Navigation labels saved.'}</p>
                    )}
                </div>
            </form>
        </AppShell>
    );
}