import { Head } from '@inertiajs/react';
import { AuthShell } from '@/components/ui/auth-shell';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/lib/i18n/locale-context';
import { ShieldCheck } from 'lucide-react';

export default function TwoFactorChallenge({ errors }: { errors?: Record<string, string> }) {
    const { locale } = useLocale();
    const ar = locale === 'ar';

    return (
        <>
            <Head title={ar ? 'التحقق بخطوتين' : 'Two-Factor Authentication'} />
            <AuthShell>
                <div className="rounded-2xl border border-border/80 bg-card p-7 shadow-[var(--shadow-panel)] sm:p-9">
                    <div className="flex size-12 items-center justify-center rounded-2xl bg-pine-800/10 text-pine-800">
                        <ShieldCheck className="size-6" aria-hidden="true" />
                    </div>
                    <div className="mt-6">
                        <h1 className="font-display text-[1.9rem] font-semibold leading-tight tracking-[-0.015em] text-ink">
                            {ar ? 'تحقق بخطوتين' : 'Two-factor authentication'}
                        </h1>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {ar
                                ? 'أدخل رمز التحقق من تطبيق المصادقة على هاتفك.'
                                : 'Enter the authentication code shown in your mobile app.'}
                        </p>
                    </div>

                    {errors?.code && (
                        <p className="mt-6 rounded-xl border border-error/20 bg-error/8 px-4 py-3 text-sm text-error">
                            {errors.code}
                        </p>
                    )}

                    <form className="mt-8 space-y-5" method="POST" action="/two-factor-challenge" noValidate>
                        <div>
                            <label htmlFor="code" className="mb-1.5 block text-sm font-medium text-foreground">
                                {ar ? 'رمز التحقق' : 'Authentication code'}
                            </label>
                            <input
                                id="code"
                                name="code"
                                type="text"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                dir="ltr"
                                required
                                placeholder="123456"
                                className="h-14 w-full rounded-xl border border-input bg-card text-center font-mono text-2xl tracking-[0.5em] text-foreground shadow-[inset_0_1px_2px_rgba(28,26,22,0.03)] transition-[border-color,box-shadow] duration-200 placeholder:text-muted-foreground/40 focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15"
                            />
                        </div>
                        <Button type="submit" size="lg" className="w-full">
                            {ar ? 'تحقق' : 'Verify'}
                        </Button>
                    </form>
                </div>
            </AuthShell>
        </>
    );
}
