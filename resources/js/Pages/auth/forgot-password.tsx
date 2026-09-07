import { Head, Link } from '@inertiajs/react';
import { AuthShell } from '@/components/ui/auth-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/lib/i18n/locale-context';
import { ArrowLeft } from 'lucide-react';

export default function ForgotPassword({
    status,
    errors,
}: { status?: string; errors?: Record<string, string> }) {
    const { locale } = useLocale();
    const ar = locale === 'ar';

    return (
        <>
            <Head title={ar ? 'نسيت كلمة المرور' : 'Forgot Password'} />
            <AuthShell>
                <div className="rounded-2xl border border-border/80 bg-card p-7 shadow-[var(--shadow-panel)] sm:p-9">
                    <div>
                        <span className="eyebrow">{ar ? 'استعادة الوصول' : 'Recover access'}</span>
                        <h1 className="mt-3 font-display text-[1.9rem] font-semibold leading-tight tracking-[-0.015em] text-ink">
                            {ar ? 'نسيت كلمة المرور؟' : 'Forgot your password?'}
                        </h1>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {ar
                                ? 'لا مشكلة — أخبرنا ببريدك الإلكتروني وسنرسل لك رابط إعادة التعيين.'
                                : 'No problem. Tell us your email and we will send you a password reset link.'}
                        </p>
                    </div>

                    {status && (
                        <p className="mt-6 rounded-xl border border-success/20 bg-success/8 px-4 py-3 text-sm text-success">
                            {status}
                        </p>
                    )}
                    {errors?.email && (
                        <p className="mt-6 rounded-xl border border-error/20 bg-error/8 px-4 py-3 text-sm text-error">
                            {errors.email}
                        </p>
                    )}

                    <form className="mt-8 space-y-5" method="POST" action="/forgot-password" noValidate>
                        <Input
                            label={ar ? 'البريد الإلكتروني' : 'Email address'}
                            name="email"
                            type="email"
                            autoComplete="email"
                            placeholder="name@school.edu.sa"
                            required
                            error={errors?.email}
                        />
                        <Button type="submit" size="lg" className="w-full">
                            {ar ? 'إرسال رابط إعادة التعيين' : 'Email password reset link'}
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
