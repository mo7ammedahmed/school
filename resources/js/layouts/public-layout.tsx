import { type ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { t, type CopyKey } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import { LanguageSwitcher } from '@/components/ui/language-switcher';
import { ThemeToggle } from '@/components/ui/theme-toggle';
import { FormFeedback } from '@/components/ui/form-feedback';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { translatedText } from '@/lib/website-content';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ArrowRight, Mail, MapPin, Menu, Phone, X } from 'lucide-react';

gsap.registerPlugin(ScrollTrigger);

interface PublicLayoutProps {
    children: ReactNode;
}

type WebsiteProps = App.PageProps & {
    websiteNavigation?: {
        title: string;
        title_ar?: string | null;
        url: string;
    }[];
    publicSchoolContact?: {
        email?: string;
        phone?: string;
        address?: string;
        address_ar?: string;
    } | null;
};

const NAV_LINKS: { key: CopyKey; href: string }[] = [
    { key: 'public.about', href: '/about' },
    { key: 'public.programs', href: '/programs' },
    { key: 'public.admissions', href: '/admissions' },
    { key: 'public.facilities', href: '/facilities' },
    { key: 'public.contact', href: '/contact' },
];

const FOOTER_COLUMNS: {
    heading: CopyKey;
    links: { key: CopyKey; href: string }[];
}[] = [
    {
        heading: 'footer.programs',
        links: [
            { key: 'public.about', href: '/about' },
            { key: 'public.programs', href: '/programs' },
            { key: 'public.facilities', href: '/facilities' },
            // `/teachers` is the dashboard's teacher resource; see routes/web.php.
            { key: 'public.teachers', href: '/faculty' },
        ],
    },
    {
        heading: 'public.admissions',
        links: [
            { key: 'public.admissions', href: '/admissions' },
            { key: 'public.applyNow', href: '/apply' },
            { key: 'public.faq', href: '/faq' },
            { key: 'public.contact', href: '/contact' },
        ],
    },
];

function BrandMark({ dark = false }: { dark?: boolean }) {
    const { locale } = useLocale();
    const { school } = usePage<WebsiteProps>().props;
    const name = translatedText(school?.name_en ?? school?.name, school?.name_ar, locale) || t(locale, 'brand.alnoor');

    return (
        <Link href="/" className="group flex items-center gap-2.5" aria-label={name}>
            <span className="relative flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-pine-600 to-pine-950 shadow-[inset_0_1px_0_rgba(255,255,255,0.25),0_2px_8px_rgba(6,40,30,0.35)]">
                <span className="font-display text-lg font-semibold leading-none text-white">{name.charAt(0)}</span>
            </span>
            <span className="flex flex-col leading-none">
                <span
                    className={cn(
                        'font-display text-base font-semibold tracking-normal',
                        dark ? 'text-footer-foreground' : 'text-header-foreground',
                    )}
                >
                    {name}
                </span>
                <span
                    className={cn(
                        'mt-1 text-2xs font-semibold uppercase tracking-widest',
                        dark ? 'text-footer-foreground/75' : 'text-header-foreground/75',
                    )}
                >
                    {locale === 'ar' ? 'التعلّم يبدأ هنا' : 'Learning starts here'}
                </span>
            </span>
        </Link>
    );
}

export default function PublicLayout({ children }: PublicLayoutProps) {
    const { locale } = useLocale();
    const page = usePage<WebsiteProps>();
    const { auth, school, publicSchoolContact: contact } = page.props;
    const navigation = (page.props.websiteNavigation ?? []).filter(
        (link) => link.url !== '/' && !NAV_LINKS.some((native) => native.href === link.url),
    );
    const url = page.url;
    const [scrolled, setScrolled] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);

    const schoolName =
        translatedText(school?.name_en ?? school?.name, school?.name_ar, locale) || t(locale, 'brand.alnoor');
    const address = translatedText(contact?.address, contact?.address_ar, locale);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 12);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        setMenuOpen(false);
    }, [url]);

    /* Entrance motion for the site chrome. */
    useEffect(() => {
        const ctx = gsap.context(() => {
            gsap.from('.public-header-inner', {
                y: -24,
                autoAlpha: 0,
                duration: 0.8,
                ease: 'power3.out',
            });
        });
        return () => ctx.revert();
    }, []);

    /* Gentle chapter reveals for non-home pages (home choreographs itself). */
    useEffect(() => {
        const mm = gsap.matchMedia();
        mm.add('(prefers-reduced-motion: no-preference)', () => {
            const sections = Array.from(document.querySelectorAll<HTMLElement>('main.public-site section')).filter(
                (el) => !el.closest('[data-home-root]'),
            );

            sections.forEach((section) => {
                const children = Array.from(section.children).filter(
                    (child) => !(child instanceof HTMLElement && child.dataset.static),
                );
                if (children.length === 0) return;
                gsap.set(children, { autoAlpha: 0, y: 26 });
                ScrollTrigger.create({
                    trigger: section,
                    start: 'top 86%',
                    once: true,
                    onEnter: () => {
                        gsap.to(children, {
                            autoAlpha: 1,
                            y: 0,
                            duration: 0.85,
                            stagger: 0.09,
                            ease: 'power3.out',
                            overwrite: 'auto',
                        });
                    },
                });
            });
            ScrollTrigger.refresh();
        });
        return () => mm.revert();
    }, []);

    // The public site follows the visitor's light/dark choice exactly like the
    // dashboard does: `buildSchoolPalettes` in app.tsx writes the school's
    // per-mode tokens (Settings → Appearance → Light/Dark) onto <html> and
    // flips the `.dark` class, which is what the marketing tokens — paper, ink
    // and the pine scale — are derived from. Claiming the document here
    // would pin the public site to light mode and fight the toggle.

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            {/* ---------- Header ---------- */}
            <header
                className={cn(
                    'public-header sticky top-0 z-50 text-header-foreground transition-[background-color,box-shadow,border-color] duration-300',
                    scrolled
                        ? 'border-b border-header-border bg-header/95 shadow-sm backdrop-blur-xl'
                        : 'border-b border-header-border bg-header',
                )}
            >
                <div className="public-header-inner">
                    <div className="mx-auto flex h-18 max-w-360 items-center justify-between gap-4 px-4 sm:px-6 lg:px-10">
                        <BrandMark />

                        {/* Desktop nav */}
                        <nav
                            className="hidden items-center gap-1 lg:flex"
                            aria-label={locale === 'ar' ? 'التنقل الرئيسي' : 'Main navigation'}
                        >
                            {NAV_LINKS.map((link) => (
                                <Link
                                    key={link.key}
                                    href={link.href}
                                    className={cn(
                                        'link-quiet rounded-full px-3.5 py-2 text-sm font-medium',
                                        url.startsWith(link.href) && link.href !== '/' && 'text-foreground',
                                    )}
                                >
                                    {t(locale, link.key)}
                                </Link>
                            ))}
                            {navigation.length > 0 && (
                                <details className="relative">
                                    <summary className="cursor-pointer rounded-full px-3 py-2 text-sm font-medium">
                                        {locale === 'ar' ? 'المزيد' : 'More'}
                                    </summary>
                                    <div className="absolute end-0 top-full mt-2 max-h-64 w-64 overflow-y-auto rounded-xl border border-header-border bg-header p-2 shadow-lg">
                                        {navigation.map((link) => (
                                            <Link
                                                key={link.url}
                                                href={link.url}
                                                className="block rounded-lg px-3 py-2 text-sm hover:bg-accent"
                                            >
                                                {translatedText(link.title, link.title_ar, locale)}
                                            </Link>
                                        ))}
                                    </div>
                                </details>
                            )}
                        </nav>

                        {/* Actions */}
                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild variant="ghost" size="sm" className="hidden md:inline-flex">
                                    <Link href="/dashboard">{t(locale, 'shell.portal')}</Link>
                                </Button>
                            ) : (
                                <Link
                                    href="/login"
                                    className="hidden text-sm font-medium text-muted-foreground transition-colors hover:text-foreground md:inline-flex"
                                >
                                    {t(locale, 'public.login')}
                                </Link>
                            )}
                            <LanguageSwitcher variant="ghost" className="hidden sm:inline-flex" />
                            <ThemeToggle className="hidden sm:inline-flex" />
                            <Button asChild className="hidden sm:inline-flex">
                                <Link href="/apply">
                                    {t(locale, 'public.applyNow')}
                                    <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                className="lg:hidden"
                                onClick={() => setMenuOpen((v) => !v)}
                                aria-label={menuOpen ? t(locale, 'common.cancel') : t(locale, 'shell.menu')}
                                aria-expanded={menuOpen}
                            >
                                {menuOpen ? (
                                    <X className="size-5" aria-hidden="true" />
                                ) : (
                                    <Menu className="size-5" aria-hidden="true" />
                                )}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Mobile panel */}
                <div
                    className={cn(
                        'overflow-hidden border-b border-header-border bg-header transition-[max-height,opacity] duration-300 ease-out lg:hidden',
                        menuOpen ? 'max-h-[75vh] overflow-y-auto opacity-100' : 'max-h-0 border-b-0 opacity-0',
                    )}
                >
                    <nav
                        inert={!menuOpen}
                        className="space-y-1 px-4 py-4"
                        aria-label={t(locale, 'a11y.mobileNavigation')}
                    >
                        {NAV_LINKS.map((link) => (
                            <Link
                                key={link.key}
                                href={link.href}
                                className="block rounded-lg px-3 py-2.5 text-base font-medium text-foreground/85 transition-colors hover:bg-accent"
                            >
                                {t(locale, link.key)}
                            </Link>
                        ))}
                        {navigation.map((link) => (
                            <Link
                                key={link.url}
                                href={link.url}
                                className="block rounded-lg px-3 py-2.5 text-base font-medium hover:bg-accent"
                            >
                                {translatedText(link.title, link.title_ar, locale)}
                            </Link>
                        ))}
                        <div className="flex flex-col gap-2.5 pt-3">
                            <Button asChild size="lg" className="w-full">
                                <Link href="/apply">{t(locale, 'public.applyNow')}</Link>
                            </Button>
                            <div className="flex items-center justify-between px-1">
                                {auth.user ? (
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href="/dashboard">{t(locale, 'shell.portal')}</Link>
                                    </Button>
                                ) : (
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href="/login">{t(locale, 'public.login')}</Link>
                                    </Button>
                                )}
                                <div className="flex items-center gap-1">
                                    <LanguageSwitcher variant="ghost" />
                                    <ThemeToggle />
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            </header>

            {/*
                Failure is reported here for every public page, the same way the
                dashboard shell reports it. The contact form flashes an error
                when the school has no inbox or the mail server refuses, and the
                page showed only the success line — so a message that was never
                sent looked exactly like one that was.
            */}
            <div className="mx-auto w-full max-w-6xl px-4 pt-6 sm:px-6 lg:px-8">
                <FormFeedback showSuccess={false} />
            </div>

            <main className="public-site flex-1">{children}</main>

            {/* ---------- Footer ---------- */}
            <footer className="relative overflow-hidden border-t border-footer-border bg-footer text-footer-foreground">
                <div className="relative mx-auto max-w-360 px-4 pb-10 pt-16 sm:px-6 lg:px-10 lg:pt-20">
                    <div className="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr]">
                        {/* Brand */}
                        <div>
                            <BrandMark dark />
                            <p className="mt-6 max-w-sm text-base leading-relaxed text-footer-foreground/80">
                                {locale === 'ar'
                                    ? 'تعرّف على مدرستنا وبرامجها وأخبار مجتمعها التعليمي.'
                                    : 'Explore our school, its programs and the news from our learning community.'}
                            </p>
                            <div className="mt-8 space-y-3 text-sm text-footer-foreground/80">
                                {address && (
                                    <p className="flex items-center gap-3">
                                        <MapPin
                                            className="size-4 shrink-0 text-footer-foreground/80"
                                            aria-hidden="true"
                                        />
                                        <span>{address}</span>
                                    </p>
                                )}
                                {contact?.phone && (
                                    <p className="flex items-center gap-3" dir="ltr">
                                        <Phone
                                            className="size-4 shrink-0 text-footer-foreground/80 rtl:-scale-x-100"
                                            aria-hidden="true"
                                        />
                                        <span className="text-start">{contact.phone}</span>
                                    </p>
                                )}
                                {contact?.email && (
                                    <p className="flex items-center gap-3">
                                        <Mail
                                            className="size-4 shrink-0 text-footer-foreground/80"
                                            aria-hidden="true"
                                        />
                                        <span>{contact.email}</span>
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Link columns */}
                        {FOOTER_COLUMNS.map((col) => (
                            <div key={col.heading}>
                                <h3 className="text-sm font-semibold uppercase tracking-widest text-footer-foreground/75">
                                    {t(locale, col.heading)}
                                </h3>
                                <ul className="mt-5 space-y-3">
                                    {col.links.map((link) => (
                                        <li key={link.key}>
                                            <Link
                                                href={link.href}
                                                className="text-base text-footer-foreground/85 transition-colors hover:text-footer-foreground"
                                            >
                                                {t(locale, link.key)}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}

                        {/* Admissions blurb */}
                        <div>
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-footer-foreground/75">
                                {t(locale, 'public.admissions')}
                            </h3>
                            <p className="mt-5 text-base leading-relaxed text-footer-foreground/80">
                                {locale === 'ar'
                                    ? 'تعرّف على خطوات القبول وقدّم طلب الالتحاق بالمدرسة.'
                                    : 'Explore the admissions process and submit your application.'}
                            </p>
                            <Button asChild variant="secondary" className="mt-6">
                                <Link href="/apply">
                                    {t(locale, 'public.applyNow')}
                                    <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                </Link>
                            </Button>
                            {navigation.length > 0 && (
                                <ul className="mt-6 space-y-3">
                                    {navigation.map((link) => (
                                        <li key={link.url}>
                                            <Link
                                                href={link.url}
                                                className="text-sm text-footer-foreground/85 hover:text-footer-foreground"
                                            >
                                                {translatedText(link.title, link.title_ar, locale)}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>

                    <div className="mt-14 flex flex-col items-center justify-between gap-4 border-t border-footer-border pt-8 sm:flex-row">
                        <p className="text-sm text-footer-foreground/75">
                            © {new Date().getFullYear()} {schoolName}. {t(locale, 'footer.rights')}
                        </p>
                        <div className="flex items-center gap-2 text-sm text-footer-foreground/75">
                            <span className="size-1.5 rounded-full bg-footer-foreground/80" aria-hidden="true" />
                            <span>{locale === 'ar' ? 'العربية · English' : 'English · العربية'}</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
