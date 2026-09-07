import { Head, Link } from '@inertiajs/react';
import { AuthShell } from '@/components/ui/auth-shell';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/lib/i18n/locale-context';
import { MailCheck } from 'lucide-react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { locale } = useLocale();
    const ar = locale === 'ar';

    return (
        <>
            <Head title={ar ? 'تحقق من البريد' : 'Verify Email'} />
            <AuthShell>
                <div className="rounded-2xl border border-border/80 bg-card p-7 text-center shadow-[var(--shadow-panel)] sm:p-9">
                    <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-pine-800/10 text-pine-800">
                        <MailCheck className="size-7" aria-hidden="true" />
                    </div>
                    <h1 className="mt-6 font-display text-[1.9rem] font-semibold leading-tight tracking-[-0.015em] text-ink">
                        {ar ? 'تحقق من بريدك الإلكتروني' : 'Verify your email address'}
                    </h1>
                    <p className="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-muted-foreground">
                        {ar
                            ? 'تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني. افتح الرابط للمتابعة.'
                            : 'A new verification link has been sent to the email address you provided. Open it to continue.'}
                    </p>

                    {status && (
                        <p className="mt-6 rounded-xl border border-success/20 bg-success/8 px-4 py-3 text-sm text-success">
                            {status}
                        </p>
                    )}

                    <Button asChild size="lg" className="mt-8 w-full">
                        <Link href="/dashboard">
                            {ar ? 'الانتقال إلى لوحة التحكم' : 'Go to dashboard'}
                        </Link>
                    </Button>
                </div>
            </AuthShell>
        </>
    );
}
