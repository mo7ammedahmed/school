import { Link, router } from '@inertiajs/react';
import { ExternalLink, FileText, Plus } from 'lucide-react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/lib/i18n/locale-context';
import { translatedText, type WebsiteLocation } from '@/lib/website-content';

type Page = {
    id: number;
    title: string;
    title_ar?: string;
    slug: string;
    status: string;
    url: string;
    show_in_navigation: boolean;
};
export default function PagesIndex({
    pages,
    locations = [],
}: {
    pages: {
        data: Page[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    locations?: (WebsiteLocation & {
        page?: { id: number; status: string } | null;
    })[];
}) {
    const { locale } = useLocale();
    const l = (en: string, ar: string) => (locale === 'ar' ? ar : en);
    const status = (value: string) =>
        ({
            draft: l('Draft', 'مسودة'),
            published: l('Published', 'منشورة'),
            scheduled: l('Scheduled', 'مجدولة'),
            archived: l('Archived', 'مؤرشفة'),
        })[value] ?? value;
    return (
        <AppShell
            title={l('Website content', 'محتوى الموقع العام')}
            breadcrumbs={[
                { label: l('Dashboard', 'لوحة التحكم'), href: '/dashboard' },
                { label: l('Website content', 'محتوى الموقع العام') },
            ]}
        >
            <PageHeader
                title={l('Website content', 'محتوى الموقع العام')}
                description={l(
                    'Manage your main pages and add new pages from one place.',
                    'عدّل الصفحات الأساسية وأضف صفحات جديدة للموقع من مكان واحد.',
                )}
                actions={
                    <Button asChild>
                        <Link href="/content/pages/create">
                            <Plus className="me-2 size-4" />
                            {l('Add page', 'إضافة صفحة')}
                        </Link>
                    </Button>
                }
            />
            <section className="mt-8">
                <h2 className="text-lg font-semibold">{l('Main public pages', 'الصفحات العامة الأساسية')}</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    {l(
                        'Configure a page, save a draft, then publish it to replace the existing content.',
                        'جهّز محتوى الصفحة واحفظه كمسودة، ثم انشره ليظهر بدل المحتوى الحالي.',
                    )}
                </p>
                <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {locations.map((location) => (
                        <div
                            key={location.slug}
                            className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <h3 className="font-semibold">
                                        {translatedText(location.title, location.title_ar, locale)}
                                    </h3>
                                    <p data-no-translate className="mt-1 text-xs text-muted-foreground" dir="ltr">
                                        {location.url}
                                    </p>
                                </div>
                                <a
                                    href={location.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    aria-label={l(`Open ${location.title}`, `فتح ${location.title_ar}`)}
                                    className="rounded p-1 text-muted-foreground hover:text-primary focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <ExternalLink className="size-4" />
                                </a>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {location.page
                                    ? status(location.page.status)
                                    : l('Default content', 'المحتوى الافتراضي')}
                            </p>
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={
                                        location.page
                                            ? `/content/pages/${location.page.id}/edit`
                                            : `/content/pages/create?location=${location.slug}`
                                    }
                                >
                                    {location.page
                                        ? l('Edit content', 'تعديل المحتوى')
                                        : l('Configure content', 'إعداد المحتوى')}
                                </Link>
                            </Button>
                        </div>
                    ))}
                </div>
            </section>
            <section className="mt-10">
                <h2 className="mb-4 text-lg font-semibold">{l('Saved pages', 'الصفحات المحفوظة')}</h2>
                {pages.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-8 text-center">
                        <FileText className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-3 text-sm text-muted-foreground">
                            {l(
                                'No pages yet. Start with a main page or add a new page.',
                                'لا توجد صفحات محفوظة بعد. ابدأ بصفحة أساسية أو أضف صفحة جديدة.',
                            )}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border border-border">
                        <table className="w-full text-start text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    {[
                                        l('Page', 'الصفحة'),
                                        l('Status', 'الحالة'),
                                        l('Menu', 'القائمة'),
                                        l('Actions', 'الإجراءات'),
                                    ].map((label) => (
                                        <th key={label} scope="col" className="px-4 py-3 text-start font-medium">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {pages.data.map((page) => (
                                    <tr key={page.id} className="border-t border-border">
                                        <td className="px-4 py-4">
                                            <p className="font-medium">
                                                {translatedText(page.title, page.title_ar, locale)}
                                            </p>
                                            <p data-no-translate dir="ltr" className="mt-1 text-xs text-muted-foreground">
                                                {page.url}
                                            </p>
                                        </td>
                                        <td className="px-4 py-4">{status(page.status)}</td>
                                        <td className="px-4 py-4">
                                            {page.show_in_navigation ? l('On publication', 'بعد النشر') : '—'}
                                        </td>
                                        <td className="px-4 py-4">
                                            <div className="flex flex-wrap gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/content/pages/${page.id}/edit`}>
                                                        {l('Edit', 'تعديل')}
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/content/pages/${page.id}/preview`} target="_blank">
                                                        {l('Preview', 'معاينة')}
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                {pages.last_page > 1 && (
                    <nav
                        aria-label={l('Page navigation', 'تنقل الصفحات')}
                        className="mt-4 flex items-center justify-between"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!pages.prev_page_url}
                            onClick={() => pages.prev_page_url && router.get(pages.prev_page_url)}
                        >
                            {l('Previous', 'السابق')}
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            {pages.current_page} / {pages.last_page}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!pages.next_page_url}
                            onClick={() => pages.next_page_url && router.get(pages.next_page_url)}
                        >
                            {l('Next', 'التالي')}
                        </Button>
                    </nav>
                )}
            </section>
            <p className="mt-6 text-sm text-muted-foreground">
                {l(
                    'News and events are managed in their own sections. School contact information is edited in school settings.',
                    'تُدار الأخبار والفعاليات من أقسامها في لوحة التحكم. يمكنك تعديل بيانات التواصل من إعدادات المدرسة.',
                )}
            </p>
        </AppShell>
    );
}
