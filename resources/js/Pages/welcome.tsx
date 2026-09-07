import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useLocale } from '@/lib/i18n/locale-context';
import { t, type CopyKey } from '@/lib/i18n/copy';
import {
    ArrowRight,
    ArrowUpRight,
    BookOpen,
    Compass,
    GraduationCap,
    HeartHandshake,
    Sparkles,
    Users,
} from 'lucide-react';

gsap.registerPlugin(ScrollTrigger);

const STATS: { count: number; suffix: string; labelKey: CopyKey }[] = [
    { count: 30, suffix: '+', labelKey: 'welcome.statYears' },
    { count: 950, suffix: '+', labelKey: 'welcome.statStudents' },
    { count: 110, suffix: '+', labelKey: 'welcome.statFaculty' },
    { count: 40, suffix: '+', labelKey: 'welcome.statActivities' },
];

const STAGES: { titleKey: CopyKey; agesKey: CopyKey; bodyKey: CopyKey }[] = [
    { titleKey: 'welcome.program1Title', agesKey: 'welcome.program1Ages', bodyKey: 'welcome.program1Body' },
    { titleKey: 'welcome.program2Title', agesKey: 'welcome.program2Ages', bodyKey: 'welcome.program2Body' },
    { titleKey: 'welcome.program3Title', agesKey: 'welcome.program3Ages', bodyKey: 'welcome.program3Body' },
    { titleKey: 'welcome.program4Title', agesKey: 'welcome.program4Ages', bodyKey: 'welcome.program4Body' },
];

const PILLARS: { titleKey: CopyKey; bodyKey: CopyKey; icon: typeof Compass }[] = [
    { titleKey: 'welcome.feature1Title', bodyKey: 'welcome.feature1Body', icon: Compass },
    { titleKey: 'welcome.feature2Title', bodyKey: 'welcome.feature2Body', icon: HeartHandshake },
    { titleKey: 'welcome.feature3Title', bodyKey: 'welcome.feature3Body', icon: Users },
];

const toLocaleDigits = (n: number, ar: boolean) =>
    ar ? String(n).replace(/\d/g, (d) => '٠١٢٣٤٥٦٧٨٩'[Number(d)]) : String(n);

export default function Welcome() {
    const { locale } = useLocale();
    const root = useRef<HTMLDivElement>(null);
    const ar = locale === 'ar';

    useEffect(() => {
        const ctx = gsap.context(() => {
            const mm = gsap.matchMedia();

            // ---------- Hero entrance ----------
            mm.add('(prefers-reduced-motion: no-preference)', () => {
                const tl = gsap.timeline({ defaults: { ease: 'power4.out' } });
                tl.fromTo(
                    '[data-hero="badge"]',
                    { y: 18, autoAlpha: 0 },
                    { y: 0, autoAlpha: 1, duration: 0.7 },
                    0.15
                )
                    .fromTo(
                        '[data-hero="line"]',
                        { yPercent: 110 },
                        { yPercent: 0, duration: 1.1, stagger: 0.12, ease: 'power4.out' },
                        0.3
                    )
                    .fromTo(
                        '[data-hero="copy"]',
                        { y: 24, autoAlpha: 0 },
                        { y: 0, autoAlpha: 1, duration: 0.8 },
                        0.75
                    )
                    .fromTo(
                        '[data-hero="visual"]',
                        { y: 40, autoAlpha: 0, scale: 0.97 },
                        { y: 0, autoAlpha: 1, scale: 1, duration: 1.1, ease: 'power3.out' },
                        0.6
                    );

                // ---------- Stats count-up ----------
                STATS.forEach((stat) => {
                    const el = root.current?.querySelector(`[data-count="${stat.labelKey}"]`);
                    if (!el) return;
                    const counter = { value: 0 };
                    ScrollTrigger.create({
                        trigger: el,
                        start: 'top 88%',
                        once: true,
                        onEnter: () => {
                            gsap.to(counter, {
                                value: stat.count,
                                duration: 1.8,
                                ease: 'power2.out',
                                onUpdate: () => {
                                    el.textContent = `${toLocaleDigits(Math.round(counter.value), ar)}${stat.suffix}`;
                                },
                            });
                        },
                    });
                });

                // ---------- Stage rows ----------
                gsap.from('[data-stage-row]', {
                    y: 34,
                    autoAlpha: 0,
                    duration: 0.8,
                    stagger: 0.1,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: '[data-journey]',
                        start: 'top 72%',
                        once: true,
                    },
                });

                // ---------- Pillars ----------
                gsap.from('[data-pillar]', {
                    y: 30,
                    autoAlpha: 0,
                    duration: 0.8,
                    stagger: 0.12,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: '[data-pillars]',
                        start: 'top 76%',
                        once: true,
                    },
                });

                // ---------- Closing panel ----------
                gsap.from('[data-cta-panel] > *', {
                    y: 26,
                    autoAlpha: 0,
                    duration: 0.8,
                    stagger: 0.1,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: '[data-cta-panel]',
                        start: 'top 82%',
                        once: true,
                    },
                });
            });
        }, root);
        return () => ctx.revert();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [locale, ar]);

    return (
        <PublicLayout>
            <div ref={root} data-home-root>
                {/* ============ HERO ============ */}
                <section className="relative overflow-hidden">
                    {/* ambient wash */}
                    <div aria-hidden="true" className="pointer-events-none absolute inset-0 -z-10">
                        <div className="absolute -top-32 start-[8%] size-[30rem] rounded-full bg-pine-100/80 blur-[120px]" />
                        <div className="absolute top-40 -end-24 size-[26rem] rounded-full bg-gold-100/90 blur-[110px]" />
                        <div className="absolute bottom-0 start-[45%] size-[22rem] rounded-full bg-pine-50 blur-[100px]" />
                    </div>

                    <div className="public-section pb-16 pt-10 sm:pb-24 md:pt-16 lg:pb-0">
                        <div className="grid items-center gap-14 lg:grid-cols-[1.12fr_0.88fr] lg:pb-24 lg:pt-10">
                            {/* Copy */}
                            <div className="relative z-10">
                                <span
                                    data-hero="badge"
                                    className="inline-flex items-center gap-2.5 rounded-full border border-pine-200/80 bg-white/70 px-4 py-2 text-[0.8125rem] font-medium text-pine-800 shadow-[0_1px_2px_rgba(28,26,22,0.04)] backdrop-blur"
                                >
                                    <span className="relative flex size-2" aria-hidden="true">
                                        <span className="absolute inline-flex size-full animate-ping rounded-full bg-pine-500 opacity-60" />
                                        <span className="relative inline-flex size-2 rounded-full bg-pine-600" />
                                    </span>
                                    {t(locale, 'welcome.badge')}
                                </span>

                                <h1 className="mt-7 text-[clamp(2.75rem,6.4vw,5.4rem)] font-display leading-[1.06]">
                                    <span data-hero="line" className="block text-balance">
                                        {t(locale, 'welcome.titleA')}
                                    </span>
                                    <span data-hero="line" className="block text-balance text-pine-700">
                                        {t(locale, 'welcome.titleB')}
                                    </span>
                                </h1>

                                <p
                                    data-hero="copy"
                                    className="mt-7 max-w-xl text-lg leading-relaxed text-foreground/70 sm:text-xl"
                                >
                                    {t(locale, 'welcome.subtitle')}
                                </p>

                                <div data-hero="copy" className="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <Button size="lg" asChild className="sm:px-8">
                                        <Link href="/apply">
                                            {t(locale, 'public.applyNow')}
                                            <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <Button size="lg" variant="outline" asChild>
                                        <Link href="/about">{t(locale, 'welcome.ctaExplore')}</Link>
                                    </Button>
                                </div>

                                <div data-hero="copy" className="mt-9 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground">
                                    <span className="inline-flex items-center gap-2">
                                        <BookOpen className="size-4 text-gold-600" aria-hidden="true" />
                                        {locale === 'ar' ? 'منهج وطني ودولي' : 'National & international curriculum'}
                                    </span>
                                    <span className="inline-flex items-center gap-2">
                                        <Sparkles className="size-4 text-gold-600" aria-hidden="true" />
                                        {locale === 'ar' ? 'صفوف صغيرة برعاية فردية' : 'Small classes, personal care'}
                                    </span>
                                </div>
                            </div>

                            {/* Visual composition */}
                            <div data-hero="visual" className="relative mx-auto w-full max-w-md lg:max-w-none">
                                <div
                                    aria-hidden="true"
                                    className="absolute -inset-6 -z-10 rounded-[3rem] bg-gradient-to-br from-pine-100/70 via-transparent to-gold-100/80 blur-2xl"
                                />
                                {/* Motto card */}
                                <div className="relative rounded-[2rem] border border-white/70 bg-white/85 p-8 shadow-[var(--shadow-panel)] backdrop-blur-sm sm:p-10">
                                    <div className="flex items-center justify-between">
                                        <span className="font-display text-[0.8125rem] font-semibold uppercase tracking-[0.22em] text-gold-600">
                                            {locale === 'ar' ? 'شعارنا' : 'Our motto'}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            className="flex size-10 items-center justify-center rounded-full border border-gold-200 bg-gold-100/60 text-gold-700"
                                        >
                                            <Sparkles className="size-4" />
                                        </span>
                                    </div>
                                    <p className="mt-6 font-display text-[1.7rem] leading-[1.25] tracking-[-0.01em] text-ink sm:text-[2rem]">
                                        {locale === 'ar'
                                            ? '«نرتقي بالعقول، ونبني الشخصية، ونُلهم القادة.»'
                                            : '“We nurture minds, build character, and inspire leaders.”'}
                                    </p>
                                    <div className="mt-7 h-px w-full bg-gradient-to-r from-gold-300/80 to-transparent" aria-hidden="true" />
                                    <div className="mt-6 flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            {locale === 'ar' ? 'الرؤية منذ ١٩٩٥' : 'The vision since 1995'}
                                        </span>
                                        <span className="inline-flex items-center gap-1.5 font-medium text-pine-700">
                                            <span className="size-1.5 rounded-full bg-pine-600" aria-hidden="true" />
                                            {locale === 'ar' ? 'الرياض' : 'Riyadh'}
                                        </span>
                                    </div>
                                </div>

                                {/* Floating mini-card */}
                                <div
                                    className="absolute -bottom-8 -start-4 hidden rounded-2xl border border-border/70 bg-white p-4 pe-6 shadow-[var(--shadow-lift)] sm:block animate-float"
                                    style={{ animationDelay: '-2.4s' }}
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-11 items-center justify-center rounded-xl bg-pine-800 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.2)]">
                                            <GraduationCap className="size-5" aria-hidden="true" />
                                        </span>
                                        <div className="leading-tight">
                                            <p className="font-display text-2xl font-semibold text-ink">
                                                {locale === 'ar' ? '١٥:١' : '15:1'}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {locale === 'ar' ? 'نسبة الطلاب إلى المعلم' : 'student–teacher ratio'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                {/* Floating ring */}
                                <div
                                    aria-hidden="true"
                                    className="absolute -top-9 -end-3 hidden size-24 rounded-full border border-gold-300/60 md:block animate-float"
                                    style={{ animationDelay: '-4.2s', animationDuration: '9s' }}
                                >
                                    <span className="absolute inset-3 rounded-full border border-gold-300/40" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ============ MARQUEE ============ */}
                <section className="border-y border-border/60 bg-white/60 py-5 backdrop-blur-sm" data-static>
                    <div className="marquee-mask overflow-hidden">
                        <div className="flex w-max animate-marquee">
                            {[0, 1].map((dup) => (
                                <span
                                    key={dup}
                                    aria-hidden={dup === 1}
                                    className="flex shrink-0 items-center font-display text-[1.05rem] tracking-wide text-foreground/45"
                                >
                                    <span className="whitespace-nowrap px-7">{t(locale, 'welcome.marquee')}</span>
                                    <span className="text-gold-500" aria-hidden="true">
                                        ◆
                                    </span>
                                </span>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ============ STATS ============ */}
                <section className="bg-paper">
                    <div className="public-section py-16 sm:py-24">
                        <dl className="grid grid-cols-2 gap-x-8 gap-y-12 lg:grid-cols-4">
                            {STATS.map((stat) => (
                                <div
                                    key={stat.labelKey}
                                    className="relative flex flex-col items-center text-center lg:items-start lg:text-start"
                                >
                                    <dt className="order-2 mt-3 text-sm font-medium text-muted-foreground">
                                        {t(locale, stat.labelKey)}
                                    </dt>
                                    <dd
                                        data-count={stat.labelKey}
                                        className="order-1 font-display text-[3.25rem] font-semibold leading-none tracking-[-0.02em] text-ink tabular-nums"
                                    >
                                        {toLocaleDigits(stat.count, ar)}
                                        {stat.suffix}
                                    </dd>
                                    <span
                                        aria-hidden="true"
                                        className="pointer-events-none absolute inset-x-1/2 top-0 h-px w-10 -translate-x-1/2 bg-gold-400/70 lg:start-0 lg:inset-x-auto lg:translate-x-0"
                                    />
                                </div>
                            ))}
                        </dl>
                    </div>
                </section>

                {/* ============ LEARNING JOURNEY ============ */}
                <section data-journey className="relative overflow-hidden bg-white">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -start-40 top-20 -z-0 size-[24rem] rounded-full bg-pine-50 blur-[100px]"
                    />
                    <div className="public-section relative py-20 sm:py-28">
                        <div className="flex flex-wrap items-end justify-between gap-6">
                            <div className="max-w-2xl">
                                <span className="eyebrow">{t(locale, 'welcome.learnKicker')}</span>
                                <h2 className="mt-4 font-display text-4xl leading-[1.08] tracking-[-0.02em] sm:text-5xl">
                                    {t(locale, 'welcome.learnTitle')}
                                </h2>
                            </div>
                            <Button variant="ghost" asChild className="text-foreground">
                                <Link href="/programs">
                                    {t(locale, 'public.programs')}
                                    <ArrowUpRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>

                        <div className="mt-14 border-t border-border/80">
                            {STAGES.map((stage, i) => (
                                <Link
                                    key={stage.titleKey}
                                    href="/programs"
                                    data-stage-row
                                    className="group grid items-center gap-3 border-b border-border/80 py-7 transition-all duration-300 hover:bg-paper/70 hover:ps-3 sm:py-8 md:grid-cols-[4.5rem_1.4fr_1fr] md:gap-8"
                                >
                                    <span className="font-display text-2xl font-medium text-gold-500/90 transition-colors group-hover:text-gold-600">
                                        {String(i + 1).padStart(2, '0')}
                                    </span>
                                    <span>
                                        <span className="block font-display text-2xl tracking-[-0.01em] text-ink transition-transform duration-300 group-hover:translate-x-1 sm:text-3xl">
                                            {t(locale, stage.titleKey)}
                                        </span>
                                        <span className="mt-2 block max-w-xl text-[0.9375rem] leading-relaxed text-muted-foreground">
                                            {t(locale, stage.bodyKey)}
                                        </span>
                                    </span>
                                    <span className="flex items-center justify-between gap-4 md:justify-end">
                                        <span className="inline-flex items-center gap-2 rounded-full border border-pine-200/70 bg-pine-50/60 px-3.5 py-1.5 text-[0.8125rem] font-medium text-pine-800">
                                            {t(locale, stage.agesKey)}
                                        </span>
                                        <span className="flex size-10 items-center justify-center rounded-full border border-border/80 bg-white text-foreground transition-all duration-300 group-hover:border-pine-700 group-hover:bg-pine-800 group-hover:text-white">
                                            <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                        </span>
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ============ WHY AL NOOR ============ */}
                <section data-pillars className="bg-paper">
                    <div className="public-section py-20 sm:py-28">
                        <div className="grid gap-14 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
                            <div className="lg:sticky lg:top-28 lg:self-start">
                                <span className="eyebrow">{t(locale, 'welcome.whyKicker')}</span>
                                <h2 className="mt-4 font-display text-4xl leading-[1.08] tracking-[-0.02em] sm:text-5xl">
                                    {t(locale, 'welcome.whyTitle')}
                                </h2>
                                <p className="mt-6 max-w-md text-lg leading-relaxed text-foreground/70">
                                    {t(locale, 'welcome.subtitle')}
                                </p>
                                <Button variant="outline" asChild className="mt-9">
                                    <Link href="/about">
                                        {t(locale, 'public.learnMore')}
                                        <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>

                            <div className="space-y-4">
                                {PILLARS.map((pillar, i) => {
                                    const Icon = pillar.icon;
                                    return (
                                        <div
                                            key={pillar.titleKey}
                                            data-pillar
                                            className="group relative overflow-hidden rounded-2xl border border-border/70 bg-white p-7 shadow-[var(--shadow-sm)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[var(--shadow-md)] sm:p-8"
                                        >
                                            <div
                                                aria-hidden="true"
                                                className="absolute -end-10 -top-10 size-32 rounded-full bg-pine-50 transition-transform duration-500 group-hover:scale-[1.7]"
                                            />
                                            <div className="relative flex items-start gap-5">
                                                <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pine-700 to-pine-950 text-white shadow-[0_6px_16px_-6px_rgba(6,40,30,0.5)]">
                                                    <Icon className="size-5" aria-hidden="true" />
                                                </span>
                                                <div className="min-w-0">
                                                    <h3 className="font-display text-xl tracking-[-0.01em] text-ink">
                                                        {t(locale, pillar.titleKey)}
                                                    </h3>
                                                    <p className="mt-2 leading-relaxed text-muted-foreground">
                                                        {t(locale, pillar.bodyKey)}
                                                    </p>
                                                </div>
                                                <span
                                                    aria-hidden="true"
                                                    className="ms-auto hidden font-display text-lg text-muted-foreground/30 sm:block"
                                                >
                                                    {String(i + 1).padStart(2, '0')}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </section>

                {/* ============ CTA ============ */}
                <section className="bg-paper pb-20 sm:pb-28">
                    <div className="public-section">
                        <div
                            data-cta-panel
                            className="surface-noise relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-pine-900 via-pine-950 to-[#04231b] px-6 py-16 text-center shadow-[var(--shadow-lift)] sm:rounded-[2.5rem] sm:px-12 sm:py-24"
                        >
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -top-24 start-1/2 h-72 w-[36rem] -translate-x-1/2 rounded-full bg-pine-600/30 blur-[100px]"
                            />
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -bottom-32 -end-20 size-80 rounded-full bg-gold-500/15 blur-[90px]"
                            />
                            <span aria-hidden="true" className="absolute inset-x-0 top-0 h-px hairline-dark" />

                            <div className="relative mx-auto max-w-3xl">
                                <span className="eyebrow" style={{ color: '#d9b970' }}>
                                    <span className="inline-flex items-center gap-2">
                                        <span className="size-1.5 rounded-full bg-gold-300" aria-hidden="true" />
                                        {locale === 'ar' ? 'العام الدراسي ٢٠٢٦–٢٠٢٧' : 'Academic year 2026–2027'}
                                    </span>
                                </span>
                                <h2 className="mt-5 font-display text-4xl leading-[1.05] tracking-[-0.02em] text-white sm:text-6xl">
                                    {t(locale, 'welcome.ctaTitle')}
                                </h2>
                                <p className="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-white/70">
                                    {t(locale, 'welcome.ctaBody')}
                                </p>
                                <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                    <Button size="lg" asChild className="w-full bg-white px-9 text-pine-950 hover:bg-white/90 sm:w-auto">
                                        <Link href="/apply">
                                            {t(locale, 'public.applyNow')}
                                            <ArrowRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <Button
                                        size="lg"
                                        variant="outline"
                                        asChild
                                        className="w-full border-white/25 bg-white/5 text-white hover:border-white/40 hover:bg-white/10 sm:w-auto"
                                    >
                                        <Link href="/contact">{t(locale, 'welcome.ctaSecondary')}</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
