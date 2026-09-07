import { Head, Link } from '@inertiajs/react';
import { AuthShell } from '@/components/ui/auth-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { useLocale } from '@/lib/i18n/locale-context';

export default function Login({ errors = {} }: { errors?: Record<string, string> }) {
    const { locale } = useLocale();
    const ar = locale === 'ar';

    return (
        <>
            <Head title={ar ? 'تسجيل الدخول' : 'Login'} />
            <AuthShell>
                <div className="rounded-2xl border border-border/80 bg-card p-7 shadow-[var(--shadow-panel)] sm:p-9">
                    <div>
                        <span className="eyebrow">
                            {ar ? 'مرحباً بعودتك' : 'Welcome back'}
                        </span>
                        <h1 className="mt-3 font-display text-[1.9rem] font-semibold leading-tight tracking-[-0.015em] text-ink">
                            {ar ? 'تسجيل الدخول إلى حسابك' : 'Sign in to your account'}
                        </h1>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {ar
                                ? 'أدخل بريدك الإلكتروني وكلمة المرور للوصول إلى بوابتك.'
                                : 'Enter your email and password to access your portal.'}
                        </p>
                    </div>

                    <form className="mt-8 space-y-5" method="POST" action="/login" noValidate>
                        <Input
                            label={ar ? 'البريد الإلكتروني' : 'Email address'}
                            name="email"
                            type="email"
                            autoComplete="email"
                            placeholder={ar ? 'name@school.edu.sa' : 'name@school.edu.sa'}
                            required
                            error={errors.email}
                        />
                        <Input
                            label={ar ? 'كلمة المرور' : 'Password'}
                            name="password"
                            type="password"
                            autoComplete="current-password"
                            placeholder="••••••••"
                            required
                            error={errors.password}
                        />

                        <div className="flex items-center justify-between gap-3">
                            <Checkbox name="remember" label={ar ? 'تذكرني' : 'Remember me'} />
                            <Link
                                href="/forgot-password"
                                className="text-[0.8125rem] font-medium text-primary transition-colors hover:text-pine-700"
                            >
                                {ar ? 'نسيت كلمة المرور؟' : 'Forgot password?'}
                            </Link>
                        </div>

                        <Button type="submit" size="lg" className="w-full">
                            {ar ? 'دخول' : 'Sign in'}
                        </Button>
                    </form>

                    <div className="mt-7 border-t border-border/70 pt-6 text-center">
                        <p className="text-sm text-muted-foreground">
                            {ar ? 'ليس لديك حساب؟ ' : 'New to Al Noor? '}
                            <Link
                                href="/apply"
                                className="font-semibold text-primary transition-colors hover:text-pine-700"
                            >
                                {ar ? 'قدّم طلب قبول' : 'Apply for admission'}
                            </Link>
                        </p>
                    </div>
                </div>
            </AuthShell>
        </>
    );
}
