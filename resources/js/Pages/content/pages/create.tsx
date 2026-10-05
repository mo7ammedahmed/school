import { Link, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Eye, Plus, Trash2 } from 'lucide-react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/lib/i18n/locale-context';
import {
    moveSection,
    translatedText,
    type ContentItem,
    type WebsiteLocation,
    type WebsitePage,
    type WebsiteSection,
} from '@/lib/website-content';

const selectStyle =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
const textStyle =
    'min-h-28 w-full rounded-md border border-input bg-background p-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

function localDate(value?: string | null) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (number: number) => String(number).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export default function PageForm({
    sectionTypes,
    page,
    initialPage,
    locations = [],
    publicUrl,
}: {
    sectionTypes: { value: string; label: string }[];
    page?: WebsitePage;
    initialPage?: WebsitePage | null;
    locations?: WebsiteLocation[];
    publicUrl?: string;
}) {
    const { locale } = useLocale();
    const l = (en: string, ar: string) => (locale === 'ar' ? ar : en);
    const editing = page?.id !== undefined;
    const source = page ?? initialPage;
    const title = editing ? l('Edit website page', 'تعديل صفحة الموقع') : l('New website page', 'إضافة صفحة للموقع');
    const form = useForm({
        title: source?.title ?? '',
        title_ar: source?.title_ar ?? '',
        slug: source?.slug ?? '',
        content: source?.content ?? '',
        content_ar: source?.content_ar ?? '',
        template: source?.template ?? 'standard',
        sections: (source?.sections ?? []).map((section) => ({
            ...section,
            content: section.content ?? {},
            settings: section.settings ?? {},
        })),
        status: source?.status ?? 'draft',
        published_at: source?.published_at ?? '',
        scheduled_at: source?.scheduled_at ?? '',
        seo_title: source?.seo_title ?? '',
        seo_description: source?.seo_description ?? '',
        canonical_url: source?.canonical_url ?? '',
        robots: source?.robots ?? 'index,follow',
        show_in_navigation: source?.show_in_navigation ?? false,
        navigation_order: source?.navigation_order ?? 0,
    });
    const { data, setData, errors, processing } = form;
    const labels: Record<string, string> = {
        hero: l('Opening section', 'القسم الافتتاحي'),
        rich_text: l('Text', 'نص تعريفي'),
        features: l('Features', 'مزايا وبطاقات'),
        stats: l('Facts and figures', 'حقائق وأرقام'),
        news: l('Latest news', 'آخر الأخبار'),
        events: l('Upcoming events', 'الفعاليات القادمة'),
        programs: l('Academic programs', 'البرامج التعليمية'),
        staff: l('Featured staff', 'فريق المدرسة'),
        faq: l('Questions and answers', 'أسئلة وأجوبة'),
        cta: l('Call to action', 'دعوة لاتخاذ إجراء'),
    };
    const location = locations.find((item) => item.slug === data.slug);
    const updateSection = (index: number, update: Partial<WebsiteSection>) =>
        setData(
            'sections',
            data.sections.map((section, i) => (i === index ? { ...section, ...update } : section)),
        );
    const updateContent = (index: number, key: keyof WebsiteSection['content'], value: string | ContentItem[]) =>
        updateSection(index, {
            content: { ...data.sections[index].content, [key]: value },
        });
    const updateItem = (sectionIndex: number, itemIndex: number, key: keyof ContentItem, value: string) =>
        updateContent(
            sectionIndex,
            'items',
            (data.sections[sectionIndex].content.items ?? []).map((item, index) =>
                index === itemIndex ? { ...item, [key]: value } : item,
            ),
        );

    return (
        <AppShell
            title={title}
            breadcrumbs={[
                { label: l('Dashboard', 'لوحة التحكم'), href: '/dashboard' },
                {
                    label: l('Website content', 'محتوى الموقع'),
                    href: '/content/pages',
                },
                { label: title },
            ]}
        >
            <PageHeader
                title={title}
                description={l(
                    'Write in Arabic or English, arrange your sections, then preview and publish.',
                    'اكتب المحتوى بالعربية أو الإنجليزية، ورتّب أقسام الصفحة ثم عاينها وانشرها.',
                )}
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" form="website-page-form" disabled={processing} className="xl:hidden">
                            {processing ? l('Saving…', 'جارٍ الحفظ…') : l('Save changes', 'حفظ التغييرات')}
                        </Button>
                        {editing && (
                            <Button variant="outline" asChild>
                                <Link href={`/content/pages/${page.id}/preview`} target="_blank">
                                    <Eye className="me-2 size-4" />
                                    {l('Preview saved page', 'معاينة الصفحة المحفوظة')}
                                </Link>
                            </Button>
                        )}
                    </div>
                }
            />
            <form
                id="website-page-form"
                onSubmit={(event) => {
                    event.preventDefault();
                    if (editing) form.put(`/content/pages/${page.id}`);
                    else form.post('/content/pages');
                }}
                className="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_19rem]"
            >
                <div className="min-w-0 space-y-6">
                    {Object.keys(errors).length > 0 && (
                        <div
                            role="alert"
                            className="rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive"
                        >
                            <p className="font-semibold">
                                {l('Please correct these fields', 'يرجى تصحيح الحقول التالية')}
                            </p>
                            <ul className="mt-2 list-inside list-disc">
                                {Object.entries(errors).map(([field, error]) => (
                                    <li key={field}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <Card>
                        <CardHeader>
                            <CardTitle>{l('Page identity', 'بيانات الصفحة')}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">{l('Title (English)', 'العنوان بالإنجليزية')}</Label>
                                <Input
                                    id="title"
                                    dir="ltr"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    aria-invalid={Boolean(errors.title)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="title_ar">{l('Title (Arabic)', 'العنوان بالعربية')}</Label>
                                <Input
                                    id="title_ar"
                                    dir="rtl"
                                    value={data.title_ar}
                                    onChange={(e) => setData('title_ar', e.target.value)}
                                    aria-invalid={Boolean(errors.title_ar)}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="destination">{l('Public page', 'الصفحة العامة')}</Label>
                                <select
                                    data-no-translate
                                    id="destination"
                                    className={selectStyle}
                                    value={location?.slug ?? 'custom'}
                                    onChange={(e) => setData('slug', e.target.value === 'custom' ? '' : e.target.value)}
                                >
                                    <option value="custom">{l('Additional page', 'صفحة إضافية')}</option>
                                    {locations.map((item) => (
                                        <option key={item.slug} value={item.slug}>
                                            {translatedText(item.title, item.title_ar, locale)} · {item.url}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {l(
                                        'Publishing a main page replaces its existing public content. Drafts stay private.',
                                        'نشر صفحة أساسية يستبدل محتواها العام الحالي. المسودات تبقى خاصة بمدير المحتوى.',
                                    )}
                                </p>
                            </div>
                            {!location && (
                                <div className="md:col-span-2">
                                    <Label htmlFor="slug">{l('Page address', 'عنوان الرابط')}</Label>
                                    <Input
                                        id="slug"
                                        dir="ltr"
                                        placeholder="school-life"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        aria-invalid={Boolean(errors.slug)}
                                    />
                                    <p data-no-translate className="mt-1 text-xs text-muted-foreground" dir="ltr">
                                        /pages/{data.slug || 'school-life'}
                                    </p>
                                </div>
                            )}
                            <div>
                                <Label htmlFor="content">
                                    {l('Introduction (English)', 'النص التعريفي بالإنجليزية')}
                                </Label>
                                <textarea
                                    id="content"
                                    dir="ltr"
                                    className={textStyle}
                                    value={data.content}
                                    onChange={(e) => setData('content', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="content_ar">
                                    {l('Introduction (Arabic)', 'النص التعريفي بالعربية')}
                                </Label>
                                <textarea
                                    id="content_ar"
                                    dir="rtl"
                                    className={textStyle}
                                    value={data.content_ar}
                                    onChange={(e) => setData('content_ar', e.target.value)}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {l('Page sections', 'أقسام الصفحة')}{' '}
                                <span className="text-sm font-normal text-muted-foreground">
                                    ({data.sections.length}/30)
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="flex flex-wrap gap-2">
                                {sectionTypes.map((type) => (
                                    <Button
                                        key={type.value}
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={data.sections.length >= 30}
                                        onClick={() =>
                                            setData('sections', [
                                                ...data.sections,
                                                {
                                                    type: type.value,
                                                    enabled: true,
                                                    content: {},
                                                    settings: {},
                                                },
                                            ])
                                        }
                                    >
                                        <Plus className="me-1 size-3.5" />
                                        {labels[type.value] ?? type.label}
                                    </Button>
                                ))}
                            </div>
                            {data.sections.length === 0 && (
                                <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                    {l(
                                        'Add a section above to start building your page.',
                                        'أضف قسمًا من الخيارات أعلاه لبدء بناء الصفحة.',
                                    )}
                                </p>
                            )}
                            {data.sections.map((section, index) => (
                                <fieldset
                                    key={`${section.type}-${index}`}
                                    className="min-w-0 space-y-4 rounded-lg border border-border p-4"
                                >
                                    <legend className="px-2 text-sm font-semibold">
                                        {index + 1}. {labels[section.type] ?? section.type}
                                    </legend>
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={section.enabled}
                                                onChange={(e) =>
                                                    updateSection(index, {
                                                        enabled: e.target.checked,
                                                    })
                                                }
                                            />
                                            {l('Visible on page', 'ظاهر في الصفحة')}
                                        </label>
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                aria-label={l(
                                                    `Move section ${index + 1} up`,
                                                    `نقل القسم ${index + 1} لأعلى`,
                                                )}
                                                disabled={index === 0}
                                                onClick={() =>
                                                    setData('sections', moveSection(data.sections, index, -1))
                                                }
                                            >
                                                <ArrowUp className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                aria-label={l(
                                                    `Move section ${index + 1} down`,
                                                    `نقل القسم ${index + 1} لأسفل`,
                                                )}
                                                disabled={index === data.sections.length - 1}
                                                onClick={() =>
                                                    setData('sections', moveSection(data.sections, index, 1))
                                                }
                                            >
                                                <ArrowDown className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                aria-label={l(`Remove section ${index + 1}`, `حذف القسم ${index + 1}`)}
                                                onClick={() =>
                                                    setData(
                                                        'sections',
                                                        data.sections.filter((_, i) => i !== index),
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {(['title', 'title_ar', 'description', 'description_ar'] as const).map(
                                            (key) => (
                                                <div key={key}>
                                                    <Label htmlFor={`section-${index}-${key}`}>
                                                        {key.startsWith('title')
                                                            ? l('Heading', 'عنوان القسم')
                                                            : l('Description', 'الوصف')}{' '}
                                                        {key.endsWith('_ar')
                                                            ? l('(Arabic)', '(عربي)')
                                                            : l('(English)', '(إنجليزي)')}
                                                    </Label>
                                                    {key.startsWith('description') ? (
                                                        <textarea
                                                            id={`section-${index}-${key}`}
                                                            dir={key.endsWith('_ar') ? 'rtl' : 'ltr'}
                                                            className={textStyle}
                                                            value={section.content[key] ?? ''}
                                                            onChange={(e) => updateContent(index, key, e.target.value)}
                                                        />
                                                    ) : (
                                                        <Input
                                                            id={`section-${index}-${key}`}
                                                            dir={key.endsWith('_ar') ? 'rtl' : 'ltr'}
                                                            value={section.content[key] ?? ''}
                                                            onChange={(e) => updateContent(index, key, e.target.value)}
                                                        />
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                    {['hero', 'cta'].includes(section.type) && (
                                        <div className="grid gap-3 md:grid-cols-3">
                                            {(['button_label', 'button_label_ar', 'button_url'] as const).map((key) => (
                                                <div key={key}>
                                                    <Label htmlFor={`section-${index}-${key}`}>
                                                        {key === 'button_url'
                                                            ? l('Button URL', 'رابط الزر')
                                                            : key.endsWith('_ar')
                                                              ? l('Button (Arabic)', 'نص الزر بالعربية')
                                                              : l('Button (English)', 'نص الزر بالإنجليزية')}
                                                    </Label>
                                                    <Input
                                                        id={`section-${index}-${key}`}
                                                        dir={key.endsWith('_ar') ? 'rtl' : 'ltr'}
                                                        placeholder={key === 'button_url' ? '/apply' : undefined}
                                                        value={section.content[key] ?? ''}
                                                        onChange={(e) => updateContent(index, key, e.target.value)}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    {['features', 'stats', 'faq', 'staff', 'programs'].includes(section.type) ? (
                                        <div className="space-y-3">
                                            {['faq', 'staff', 'programs'].includes(section.type) && (
                                                <p className="text-xs text-muted-foreground">
                                                    {l(
                                                        'Add your own items to replace the automatic school listing in this section. Leave empty to use the public school records.',
                                                        'أضف عناصر مخصصة لاستبدال القائمة التلقائية في هذا القسم، أو اتركه فارغًا لعرض سجلات المدرسة المتاحة للعامة.',
                                                    )}
                                                </p>
                                            )}
                                            {(section.content.items ?? []).map((item, itemIndex) => (
                                                <fieldset key={itemIndex} className="rounded-md bg-muted/40 p-3">
                                                    <legend className="px-1 text-xs font-semibold">
                                                        {l('Item', 'العنصر')} {itemIndex + 1}
                                                    </legend>
                                                    <div className="grid gap-3 md:grid-cols-2">
                                                        {(
                                                            [
                                                                'title',
                                                                'title_ar',
                                                                'description',
                                                                'description_ar',
                                                            ] as const
                                                        ).map((key) => (
                                                            <div key={key}>
                                                                <Label htmlFor={`item-${index}-${itemIndex}-${key}`}>
                                                                    {key.startsWith('title')
                                                                        ? section.type === 'faq'
                                                                            ? l('Question', 'السؤال')
                                                                            : section.type === 'stats'
                                                                              ? l('Value', 'القيمة')
                                                                              : l('Title', 'العنوان')
                                                                        : section.type === 'faq'
                                                                          ? l('Answer', 'الإجابة')
                                                                          : l('Description', 'الوصف')}{' '}
                                                                    {key.endsWith('_ar') ? '(عربي)' : '(English)'}
                                                                </Label>
                                                                <textarea
                                                                    id={`item-${index}-${itemIndex}-${key}`}
                                                                    dir={key.endsWith('_ar') ? 'rtl' : 'ltr'}
                                                                    className={`${textStyle} min-h-20`}
                                                                    value={item[key] ?? ''}
                                                                    onChange={(e) =>
                                                                        updateItem(
                                                                            index,
                                                                            itemIndex,
                                                                            key,
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                        ))}
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        className="mt-2 text-destructive"
                                                        onClick={() =>
                                                            updateContent(
                                                                index,
                                                                'items',
                                                                (section.content.items ?? []).filter(
                                                                    (_, i) => i !== itemIndex,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        {l('Remove item', 'حذف العنصر')}
                                                    </Button>
                                                </fieldset>
                                            ))}
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                disabled={(section.content.items?.length ?? 0) >= 12}
                                                onClick={() =>
                                                    updateContent(index, 'items', [
                                                        ...(section.content.items ?? []),
                                                        {
                                                            title: '',
                                                            title_ar: '',
                                                            description: '',
                                                            description_ar: '',
                                                        },
                                                    ])
                                                }
                                            >
                                                <Plus className="me-1 size-4" />
                                                {l('Add item', 'إضافة عنصر')}
                                            </Button>
                                        </div>
                                    ) : (
                                        ['news', 'events', 'programs', 'staff'].includes(section.type) && (
                                            <div className="space-y-2">
                                                <p className="text-xs text-muted-foreground">
                                                    {l(
                                                        'Shows current published school content automatically.',
                                                        'يعرض المحتوى المتاح للعامة في هذه المدرسة تلقائيًا.',
                                                    )}
                                                </p>
                                                <Label htmlFor={`limit-${index}`}>
                                                    {l('Number of items', 'عدد العناصر')}
                                                </Label>
                                                <Input
                                                    id={`limit-${index}`}
                                                    className="max-w-24"
                                                    type="number"
                                                    min={1}
                                                    max={12}
                                                    value={section.settings.limit ?? 3}
                                                    onChange={(e) =>
                                                        updateSection(index, {
                                                            settings: {
                                                                limit: Number(e.target.value),
                                                            },
                                                        })
                                                    }
                                                />
                                            </div>
                                        )
                                    )}
                                </fieldset>
                            ))}
                        </CardContent>
                    </Card>
                    <details className="rounded-lg border border-border p-4">
                        <summary className="cursor-pointer font-semibold">
                            {l('Search engine settings', 'إعدادات محركات البحث')}
                        </summary>
                        <div className="mt-4 space-y-4">
                            {(['seo_title', 'seo_description', 'canonical_url'] as const).map((key) => (
                                <div key={key}>
                                    <Label htmlFor={key}>
                                        {key === 'seo_title'
                                            ? l('Search title', 'عنوان البحث')
                                            : key === 'seo_description'
                                              ? l('Search description', 'وصف البحث')
                                              : l('Canonical URL', 'الرابط الأساسي')}
                                    </Label>
                                    <Input
                                        id={key}
                                        value={data[key]}
                                        maxLength={key === 'seo_description' ? 160 : undefined}
                                        onChange={(e) => setData(key, e.target.value)}
                                    />
                                </div>
                            ))}
                            <Label htmlFor="robots">{l('Search visibility', 'الظهور في البحث')}</Label>
                            <select
                                id="robots"
                                className={selectStyle}
                                value={data.robots}
                                onChange={(e) => setData('robots', e.target.value)}
                            >
                                <option value="index,follow">{l('Allow indexing', 'السماح بالفهرسة')}</option>
                                <option value="noindex,follow">{l('Do not index', 'عدم الفهرسة')}</option>
                                <option value="index,nofollow">index,nofollow</option>
                                <option value="noindex,nofollow">noindex,nofollow</option>
                            </select>
                        </div>
                    </details>
                </div>
                <aside className="space-y-4 xl:sticky xl:top-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{l('Publication', 'النشر')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="status">{l('Status', 'حالة الصفحة')}</Label>
                                <select
                                    id="status"
                                    className={selectStyle}
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                >
                                    <option value="draft">{l('Draft — private', 'مسودة — خاصة')}</option>
                                    <option value="published">{l('Published', 'منشورة')}</option>
                                    <option value="scheduled">{l('Scheduled', 'نشر مجدول')}</option>
                                    <option value="archived">{l('Archived', 'مؤرشفة')}</option>
                                </select>
                            </div>
                            {data.status === 'scheduled' && (
                                <div>
                                    <Label htmlFor="scheduled_at">
                                        {l('Publish at (your local time)', 'موعد النشر بتوقيت جهازك')}
                                    </Label>
                                    <Input
                                        id="scheduled_at"
                                        type="datetime-local"
                                        required
                                        value={localDate(data.scheduled_at)}
                                        onChange={(e) =>
                                            setData(
                                                'scheduled_at',
                                                e.target.value ? new Date(e.target.value).toISOString() : '',
                                            )
                                        }
                                    />
                                </div>
                            )}
                            <p data-no-translate className="break-all text-xs text-muted-foreground" dir="ltr">
                                {location?.url ?? `/pages/${data.slug || '…'}`}
                            </p>
                            <Button type="submit" disabled={processing} className="w-full">
                                {processing ? l('Saving…', 'جارٍ الحفظ…') : l('Save changes', 'حفظ التغييرات')}
                            </Button>
                            <Button type="button" variant="outline" className="w-full" asChild>
                                <Link href="/content/pages">{l('Back to pages', 'العودة للصفحات')}</Link>
                            </Button>
                            {publicUrl && (
                                <a
                                    href={publicUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="block text-center text-sm text-primary underline"
                                >
                                    {l('Open public URL', 'فتح الرابط العام')}
                                </a>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{l('Navigation', 'قائمة الموقع')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <label className="flex items-start gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    className="mt-1"
                                    checked={data.show_in_navigation}
                                    onChange={(e) => setData('show_in_navigation', e.target.checked)}
                                />
                                {l(
                                    'Add to the public site menu after publishing',
                                    'إظهار الصفحة في قائمة الموقع بعد نشرها',
                                )}
                            </label>
                            <div>
                                <Label htmlFor="navigation_order">{l('Menu order', 'ترتيب الرابط')}</Label>
                                <Input
                                    id="navigation_order"
                                    type="number"
                                    min={0}
                                    max={999}
                                    value={data.navigation_order}
                                    onChange={(e) => setData('navigation_order', Number(e.target.value))}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <p className="px-1 text-xs leading-relaxed text-muted-foreground">
                        {l(
                            'Preview shows saved changes only. Confirm your school information and figures before publishing.',
                            'المعاينة تعرض التغييرات المحفوظة فقط. تأكد من صحة معلومات المدرسة والأرقام قبل النشر.',
                        )}
                    </p>
                </aside>
            </form>
        </AppShell>
    );
}
