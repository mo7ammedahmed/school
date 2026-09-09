import { type ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { t, type CopyKey } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import { LanguageSwitcher } from '@/components/ui/language-switcher';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ArrowRight, Mail, MapPin, Menu, Phone, X } from 'lucide-react';

gsap.registerPlugin(ScrollTrigger);

interface PublicLayoutProps {
    children: ReactNode;
}

const NAV_LINKS: { key: CopyKey; href: string }[] = [
    { key: 'public.about', href: '/about' },
    { key: 'public.programs', href: '/programs' },
    { key: 'public.admissions', href: '/admissions' },
    { key: 'public.facilities', href: '/facilities' },
    { key: 'public.contact', href: '/contact' },
];

const FOOTER_COLUMNS: { heading: CopyKey; links: { key: CopyKey; href: string }[] }[] = [
    {
        heading: 'footer.programs',
        links: [
            { key: 'public.about', href: '/about' },
            { key: 'public.programs', href: '/programs' },
            { key: 'public.facilities', href: '/facilities' },
            { key: 'public.teachers', href: '/teachers' },
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

    return (
        <Link href="/" className="group flex items-center gap-2.5" aria-label={t(locale, 'brand.alnoor')}>
            <span className="relative flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-pine-600 to-pine-950 shadow-[inset_0_1px_0_rgba(255,255,255,0.25),0_2px_8px_rgba(6,40,30,0.35)]">
                <span className="font-display text-lg font-semibold leading-none text-white">A</span>
            </span>
            <span className="flex flex-col leading-none">
                <span
                    className={cn(
                        'font-display text-base font-semibold tracking-normal',
                        dark ? 'text-white' : 'text-ink'
                    )}
                >
                    {t(locale, 'brand.alnoor')}
                </span>
                <span
                    className={cn(
                        'mt-1 text-2xs font-semibold uppercase tracking-widest',
                        dark ? 'text-white/45' : 'text-muted-foreground'
                    )}
                >
                    Est. 1995 · Riyadh
                </span>
            </span>
        </Link>
    );
}

export default function PublicLayout({ children }: PublicLayoutProps) {
    const { locale } = useLocale();
    const page = usePage<App.PageProps>();
    const { auth } = page.props;
    const url = page.url;
    const [scrolled, setScrolled] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);

    const schoolName = t(locale, 'brand.alnoor');

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
            const sections = Array.from(
                document.querySelectorAll<HTMLElement>('main.public-site section')
            ).filter((el) => !el.closest('[data-home-root]'));

            sections.forEach((section) => {
                const children = Array.from(section.children).filter(
                    (child) => !(child instanceof HTMLElement && child.dataset.static)
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



    // Apply the school's LIGHT theme tokens to the public site. The entire
    // marketing site — header, footer, hero, buttons, footer blur, sidebar
    // accents — is driven by these tokens, so the dashboard can recolor the
    // whole public site from Settings → Appearance → Website Colors.
    useEffect(() => {
        const school = page.props.school;
        const theme = school?.theme_config?.light;
        if (!theme) return;

        const root = document.documentElement;
        root.classList.remove('dark');
        root.dataset.theme = 'light';

        // Every token the public site cares about gets written as a CSS variable.
        Object.entries(theme).forEach(([key, value]) => {
            if (typeof value !== 'string') return;
            const cssVar = key.replace(/[A-Z]/g, (match) => `-${match.toLowerCase()}`);
            root.style.setProperty(`--${cssVar}`, value);
        });

        // Public-site brand accents: the pine/gold scale is derived from the
        // themed primary color, not a hardcoded hex.
        root.style.setProperty('--color-pine-600', theme.colorPrimary ?? '#0a5c42');
        root.style.setProperty('--color-pine-950', theme.colorPrimary ?? '#0a5c42');
        root.style.setProperty('--color-gold-400', theme.colorAccent ?? '#cda253');
        root.style.setProperty('--color-gold-600', theme.colorMutedForeground ?? '#74705f');
    }, [page.props.school]);

    return (
        <div className="flex min-h-screen flex-col bg-paper">
            {/* ---------- Header ---------- */}
            <header
                className={cn(
                    'sticky top-0 z-50 transition-[background-color,box-shadow,border-color] duration-300',
                    scrolled
                        ? 'border-b border-border/60 bg-paper/85 shadow-[0_1px_0_rgba(28,26,22,0.02)] backdrop-blur-xl'
                        : 'border-b border-transparent bg-transparent'
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
                                        url.startsWith(link.href) && link.href !== '/' && 'text-foreground'
                                    )}
                                >
                                    {t(locale, link.key)}
                                </Link>
                            ))}
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
                        'overflow-hidden border-b border-border/60 bg-paper transition-[max-height,opacity] duration-300 ease-out lg:hidden',
                        menuOpen ? 'max-h-128 opacity-100' : 'max-h-0 border-b-0 opacity-0'
                    )}
                >
                    <nav className="space-y-1 px-4 py-4" aria-label="Mobile navigation">
                        {NAV_LINKS.map((link) => (
                            <Link
                                key={link.key}
                                href={link.href}
                                className="block rounded-lg px-3 py-2.5 text-base font-medium text-foreground/85 transition-colors hover:bg-accent"
                            >
                                {t(locale, link.key)}
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
                                <LanguageSwitcher variant="ghost" />
                            </div>
                        </div>
                    </nav>
                </div>
            </header>

            <main className="public-site flex-1">{children}</main>

            {/* ---------- Footer ---------- */}
            <footer className="relative overflow-hidden bg-pine-950 text-white">
                <div aria-hidden="true" className="pointer-events-none absolute inset-x-0 top-0 h-px hairline-dark" />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -end-40 -top-52 size-136 rounded-full bg-pine-800/30 blur-[110px]"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -start-32 bottom-0 size-96 rounded-full bg-gold-600/10 blur-[100px]"
                />

                <div className="relative mx-auto max-w-360 px-4 pb-10 pt-16 sm:px-6 lg:px-10 lg:pt-20">
                    <div className="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr]">
                        {/* Brand */}
                        <div>
                            <BrandMark dark />
                            <p className="mt-6 max-w-sm text-base leading-relaxed text-white/60">
                                {t(locale, 'footer.tagline')}
                            </p>
                            <div className="mt-8 space-y-3 text-sm text-white/60">
                                <p className="flex items-center gap-3">
                                    <MapPin className="size-4 shrink-0 text-gold-300/80" aria-hidden="true" />
                                    <span>
                                        {t(locale, 'footer.addressLine1')}، {t(locale, 'footer.addressLine2')}
                                    </span>
                                </p>
                                <p className="flex items-center gap-3" dir="ltr">
                                    <Phone className="size-4 shrink-0 text-gold-300/80 rtl:-scale-x-100" aria-hidden="true" />
                                    <span className="text-start">+966 50 123 4567</span>
                                </p>
                                <p className="flex items-center gap-3">
                                    <Mail className="size-4 shrink-0 text-gold-300/80" aria-hidden="true" />
                                    <span>info@alnoor.school</span>
                                </p>
                            </div>
                        </div>

                        {/* Link columns */}
                        {FOOTER_COLUMNS.map((col) => (
                            <div key={col.heading}>
                                <h3 className="text-sm font-semibold uppercase tracking-widest text-white/40">
                                    {t(locale, col.heading)}
                                </h3>
                                <ul className="mt-5 space-y-3">
                                    {col.links.map((link) => (
                                        <li key={link.key}>
                                            <Link
                                                href={link.href}
                                                className="text-base text-white/70 transition-colors hover:text-gold-200"
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
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-white/40">
                                {locale === 'ar' ? 'القبول ٢٠٢٦–٢٠٢٧' : 'Admissions 2026–2027'}
                            </h3>
                            <p className="mt-5 text-base leading-relaxed text-white/60">
                                {locale === 'ar'
                                    ? 'التقديم مفتوح الآن للعام الدراسي القادم. نسعد باستقبالكم في جولة تعرّف على حرمنا المدرسي.'
                                    : 'Applications are open for the coming academic year. Visit us for a personal tour of campus.'}
                            </p>
                            <Button asChild variant="secondary" className="mt-6 bg-white text-pine-950 hover:bg-white/90">
                                <Link href="/apply">
                                    {t(locale, 'public.applyNow')}
                                    <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="mt-14 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
                        <p className="text-sm text-white/40">
                            © {new Date().getFullYear()} {schoolName}. {t(locale, 'footer.rights')}
                        </p>
                        <div className="flex items-center gap-2 text-sm text-white/40">
                            <span className="size-1.5 rounded-full bg-gold-300/80" aria-hidden="true" />
                            <span>{locale === 'ar' ? 'العربية · English' : 'English · العربية'}</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

