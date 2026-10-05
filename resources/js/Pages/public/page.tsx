import { Link, useForm } from '@inertiajs/react';
import { ArrowRight, Mail } from 'lucide-react';
import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormFeedback } from '@/components/ui/form-feedback';
import { useLocale } from '@/lib/i18n/locale-context';
import {
    safeWebsiteUrl,
    translatedText,
    type ContentItem,
    type WebsitePage,
    type WebsiteSection,
} from '@/lib/website-content';

function ContactForm() {
    const { locale } = useLocale();
    const l = (en: string, ar: string) => (locale === 'ar' ? ar : en);
    const form = useForm({ name: '', email: '', subject: '', message: '' });
    return (
        <section className="mx-auto max-w-3xl px-6 py-12">
            <h2 className="flex items-center gap-3 font-display text-2xl">
                <Mail className="size-5" />
                {l('Send us a message', 'أرسل رسالة للمدرسة')}
            </h2>
            <FormFeedback />
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/contact', { onSuccess: () => form.reset() });
                }}
                className="mt-6 space-y-4"
            >
                {(['name', 'email', 'subject', 'message'] as const).map((key) => (
                    <div key={key}>
                        <Label htmlFor={`contact-${key}`}>
                            {
                                {
                                    name: l('Name', 'الاسم'),
                                    email: l('Email', 'البريد الإلكتروني'),
                                    subject: l('Subject', 'الموضوع'),
                                    message: l('Message', 'الرسالة'),
                                }[key]
                            }
                        </Label>
                        {key === 'message' ? (
                            <textarea
                                id={`contact-${key}`}
                                required
                                className="min-h-36 w-full rounded-md border border-input bg-background p-3 text-sm"
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                            />
                        ) : (
                            <Input
                                id={`contact-${key}`}
                                required
                                type={key === 'email' ? 'email' : 'text'}
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                            />
                        )}
                        {form.errors[key] && (
                            <p role="alert" className="mt-1 text-sm text-destructive">
                                {form.errors[key]}
                            </p>
                        )}
                    </div>
                ))}
                <Button disabled={form.processing}>
                    {form.processing ? l('Sending…', 'جارٍ الإرسال…') : l('Send message', 'إرسال الرسالة')}
                </Button>
            </form>
        </section>
    );
}

export function WebsiteSectionView({
    section,
    collection = [],
    heading = false,
}: {
    section: WebsiteSection;
    collection?: ContentItem[];
    heading?: boolean;
}) {
    const { locale } = useLocale();
    if (!section.enabled) return null;
    const title = translatedText(section.content?.title, section.content?.title_ar, locale);
    const description = translatedText(section.content?.description, section.content?.description_ar, locale);
    const button = translatedText(section.content?.button_label, section.content?.button_label_ar, locale);
    const href = safeWebsiteUrl(section.content?.button_url);
    const authoredItems = section.content?.items ?? [];
    const items = (authoredItems.length > 0 ? authoredItems : collection).slice(0, section.settings?.limit ?? 12);
    const Heading = heading ? 'h1' : 'h2';

    if (section.type === 'hero' || section.type === 'cta') {
        return (
            <section
                className={
                    section.type === 'hero'
                        ? 'border-b border-border bg-secondary/40'
                        : 'bg-primary text-primary-foreground'
                }
            >
                <div className="mx-auto max-w-6xl px-6 py-16 md:py-24">
                    <Heading className="max-w-4xl font-display text-4xl leading-tight tracking-tight md:text-6xl">
                        {title}
                    </Heading>
                    {description && (
                        <p
                            className={`mt-6 max-w-3xl whitespace-pre-line text-lg leading-relaxed ${section.type === 'cta' ? 'text-primary-foreground/80' : 'text-muted-foreground'}`}
                        >
                            {description}
                        </p>
                    )}
                    {button && href && (
                        <Button className="mt-8" variant={section.type === 'cta' ? 'secondary' : 'default'} asChild>
                            <a href={href}>
                                {button}
                                <ArrowRight className="ms-2 size-4 rtl:-scale-x-100" />
                            </a>
                        </Button>
                    )}
                </div>
            </section>
        );
    }
    return (
        <section className="mx-auto max-w-6xl px-6 py-12 md:py-16">
            {title && <Heading className="font-display text-3xl tracking-tight md:text-4xl">{title}</Heading>}
            {description && (
                <p className="mt-4 max-w-3xl whitespace-pre-line text-base leading-relaxed text-muted-foreground">
                    {description}
                </p>
            )}
            {section.type === 'faq' ? (
                <div className="mt-7 max-w-4xl divide-y divide-border border-y border-border">
                    {items.map((item, i) => (
                        <details key={i} className="group py-5">
                            <summary className="cursor-pointer font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {translatedText(item.title, item.title_ar, locale)}
                            </summary>
                            <p className="mt-4 whitespace-pre-line leading-relaxed text-muted-foreground">
                                {translatedText(item.description, item.description_ar, locale)}
                            </p>
                        </details>
                    ))}
                </div>
            ) : section.type === 'stats' ? (
                <dl className="mt-7 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map((item, i) => (
                        <div key={i} className="border-s-2 border-primary ps-5">
                            <dt className="text-sm text-muted-foreground">
                                {translatedText(item.description, item.description_ar, locale)}
                            </dt>
                            <dd className="mt-2 font-display text-4xl font-semibold">
                                {translatedText(item.title, item.title_ar, locale)}
                            </dd>
                        </div>
                    ))}
                </dl>
            ) : (
                section.type !== 'rich_text' && (
                    <div className="mt-7 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {items.map((item, i) => (
                            <article key={i} className="min-w-0 border-t-2 border-primary/40 py-6">
                                <h3 className="font-display text-xl font-semibold">
                                    {translatedText(item.title, item.title_ar, locale)}
                                </h3>
                                {(item.description || item.description_ar) && (
                                    <p className="mt-3 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                        {translatedText(item.description, item.description_ar, locale)}
                                    </p>
                                )}
                                {safeWebsiteUrl(item.url) && (
                                    <a
                                        href={safeWebsiteUrl(item.url)}
                                        className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-primary dark:text-pine-200 underline underline-offset-4"
                                    >
                                        {locale === 'ar' ? 'اقرأ المزيد' : 'Read more'}
                                        <ArrowRight className="size-4 rtl:-scale-x-100" />
                                    </a>
                                )}
                            </article>
                        ))}
                    </div>
                )
            )}
            {items.length === 0 && ['news', 'events', 'programs', 'staff', 'faq'].includes(section.type) && (
                <p className="mt-5 text-sm text-muted-foreground">
                    {locale === 'ar'
                        ? 'سنضيف المزيد من المعلومات هنا قريبًا.'
                        : 'More information will be added here soon.'}
                </p>
            )}
        </section>
    );
}

export default function PublicPage({
    page,
    collections = {},
    preview = false,
    editorUrl,
}: {
    page: WebsitePage;
    collections?: Record<string, ContentItem[]>;
    preview?: boolean;
    editorUrl?: string | null;
}) {
    const { locale } = useLocale();
    const sections = (page.sections ?? []).filter((section) => section.enabled);
    const firstIsHero = sections[0]?.type === 'hero';
    const body = translatedText(page.content, page.content_ar, locale);
    return (
        <PublicLayout>
            {preview && (
                <div role="status" className="border-y border-primary/30 bg-primary/10 px-6 py-4">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 text-sm">
                        <p>
                            {locale === 'ar'
                                ? 'معاينة خاصة — هذه الصفحة ليست منشورة بهذه المعاينة.'
                                : 'Private preview — visitors cannot access this preview.'}
                        </p>
                        {editorUrl && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={editorUrl}>{locale === 'ar' ? 'العودة للمحرّر' : 'Back to editor'}</Link>
                            </Button>
                        )}
                    </div>
                </div>
            )}
            {firstIsHero ? (
                <WebsiteSectionView section={sections[0]} heading />
            ) : (
                <header className="mx-auto max-w-6xl px-6 pb-6 pt-16">
                    <h1 className="font-display text-4xl leading-tight tracking-tight md:text-5xl">
                        {translatedText(page.title, page.title_ar, locale)}
                    </h1>
                </header>
            )}
            {body && (
                <div className="mx-auto max-w-4xl whitespace-pre-line px-6 py-10 text-lg leading-relaxed text-foreground/85">
                    {body}
                </div>
            )}
            {(firstIsHero ? sections.slice(1) : sections).map((section, index) => (
                <WebsiteSectionView
                    key={`${section.type}-${index}`}
                    section={section}
                    collection={collections[section.type]}
                />
            ))}
            {page.slug === 'contact' && <ContactForm />}
        </PublicLayout>
    );
}
