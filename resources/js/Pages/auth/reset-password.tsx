import { Head, Link } from '@inertiajs/react';
import { AuthShell } from '@/components/ui/auth-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/lib/i18n/locale-context';
import { ArrowLeft } from 'lucide-react';

export default function ResetPassword({
    email,
    token,
    errors,
}: { email?: string; token?: string; errors?: Record<string, string> }) {
    const { locale } = useLocale();
    const ar = locale === 'ar';

    return (
        <>
            <Head title={ar ? 'إعادة تعيين كلمة المرور' : 'Reset Password'} />
            <AuthShell>
                <div className="rounded-2xl border border-border/80 bg-card p-7 shadow-[var(--shadow-panel)] sm:p-9">
                    <div>
                        <span className="eyebrow">{ar ? 'خطوة أخيرة' : 'One last step'}</span>
                        <h1 className="mt-3 font-display text-[1.9rem] font-semibold leading-tight tracking-[-0.015em] text-ink">
                            {ar ? 'تعيين كلمة مرور جديدة' : 'Set a new password'}
                        </h1>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {ar
                                ? 'اختر كلمة مرور قوية لا تستخدمها في مواقع أخرى.'
                                : 'Choose a strong password you do not use elsewhere.'}
                        </p>
                    </div>

                    <form className="mt-8 space-y-5" method="POST" action="/reset-password" noValidate>
                        <input type="hidden" name="token" value={token || ''} />
                        <Input
                            label={ar ? 'البريد الإلكتروني' : 'Email address'}
                            name="email"
                            type="email"
                            autoComplete="email"
                            defaultValue={email}
                            required
                            error={errors?.email}
                        />
                        <Input
                            label={ar ? 'كلمة المرور الجديدة' : 'New password'}
                            name="password"
                            type="password"
                            autoComplete="new-password"
                            placeholder="••••••••"
                            required
                            error={errors?.password}
                        />
                        <Input
                            label={ar ? 'تأكيد كلمة المرور' : 'Confirm password'}
                            name="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            placeholder="••••••••"
                            required
                            error={errors?.password_confirmation}
                        />
                        <Button type="submit" size="lg" className="w-full">
                            {ar ? 'إعادة تعيين كلمة المرور' : 'Reset password'}
                        </Button>
                    </form>

                    <div className="mt-6 text-center">
                        <Link
                            href="/login"
                            className="inline-flex items-center gap-1.5 text-[0.8125rem] font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <ArrowLeft className="size-3.5 rtl:-scale-x-100" aria-hidden="true" />
                            {ar ? 'العودة لتسجيل الدخول' : 'Back to login'}
                        </Link>
                    </div>
                </div>
            </AuthShell>
        </>
    );
}
