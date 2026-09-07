import { type ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { useLocale } from '@/lib/i18n/locale-context';
import { t } from '@/lib/i18n/copy';
import { ShieldCheck, Sparkles } from 'lucide-react';

export function AuthShell({ children }: { children: ReactNode }) {
    const { locale } = useLocale();
    const rtl = locale === 'ar';

    return (
        <div className="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
            {/* Brand panel */}
            <div className="relative hidden overflow-hidden bg-gradient-to-br from-pine-900 via-pine-950 to-[#04231b] lg:flex lg:flex-col lg:justify-between">
                <div aria-hidden="true" className="surface-noise pointer-events-none absolute inset-0" />
                <div aria-hidden="true" className="pointer-events-none absolute -top-40 start-1/4 size-[34rem] rounded-full bg-pine-700/40 blur-[130px]" />
                <div aria-hidden="true" className="pointer-events-none absolute -bottom-44 -end-24 size-[30rem] rounded-full bg-gold-500/10 blur-[120px]" />
                <span aria-hidden="true" className="absolute inset-x-0 top-0 h-px hairline-dark" />

                <div className="relative p-12 xl:p-16">
                    <Link href="/" className="inline-flex items-center gap-3">
                        <span className="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-pine-600 to-pine-950 shadow-[inset_0_1px_0_rgba(255,255,255,0.25)] ring-1 ring-white/10">
                            <span className="font-display text-xl font-semibold text-white">A</span>
                        </span>
                        <span className="flex flex-col leading-none">
                            <span className="font-display text-lg font-semibold tracking-[-0.01em] text-white">
                                {t(locale, 'brand.aether')}
                            </span>
                            <span className="mt-1 text-[0.6rem] font-semibold uppercase tracking-[0.22em] text-white/40">
                                {rtl ? 'نظام إدارة المدارس' : 'School OS'}
                            </span>
                        </span>
                    </Link>
                </div>

                <div className="relative px-12 pb-16 xl:px-16">
                    <span className="flex items-center gap-3 text-[0.78rem] font-semibold uppercase tracking-[0.2em] text-gold-300">
                        <Sparkles className="size-4" aria-hidden="true" />
                        {rtl ? 'منذ ١٩٩٥' : 'Since 1995'}
                    </span>
                    <p className="mt-6 max-w-md font-display text-[2.4rem] font-medium leading-[1.18] tracking-[-0.015em] text-white">
                        {t(locale, 'footer.tagline').replace(/\.$/, '')}.
                    </p>
                    <div className="mt-8 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-white/55">
                        <span className="inline-flex items-center gap-2">
                            <ShieldCheck className="size-4 text-gold-300/90" aria-hidden="true" />
                            {rtl ? 'تسجيل دخول آمن' : 'Secure sign-in'}
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <span className="size-1.5 rounded-full bg-gold-300" aria-hidden="true" />
                            {rtl ? 'بوابات للهيئة والطلاب وأولياء الأمور' : 'Portals for staff, students & parents'}
                        </span>
                    </div>
                </div>
            </div>

            {/* Form panel */}
            <div className="relative flex items-center justify-center bg-paper px-4 py-10 sm:px-8 lg:bg-background">
                <div className="w-full max-w-[26.5rem]">
                    {/* Compact brand for mobile */}
                    <Link href="/" className="mb-8 inline-flex items-center gap-2.5 lg:hidden">
                        <span className="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-pine-600 to-pine-950 shadow-sm">
                            <span className="font-display text-lg font-semibold text-white">A</span>
                        </span>
                        <span className="font-display text-[1.02rem] font-semibold tracking-[-0.01em] text-ink">
                            {t(locale, 'brand.aether')}
                        </span>
                    </Link>
                    {children}
                </div>
            </div>
        </div>
    );
}
