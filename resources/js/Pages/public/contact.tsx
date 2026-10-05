import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Phone, Mail, MapPin, Clock } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import { usePage, router } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';

interface ContactForm {
    name: string;
    email: string;
    subject: string;
    message: string;
}

export default function Contact() {
    const { locale } = useLocale();
    const { flash } = usePage().props;

    const {
        register,
        handleSubmit,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<ContactForm>();

    const onSubmit = (data: ContactForm) => {
        router.post('/contact', data as unknown as RequestPayload, {
            onSuccess: () => reset(),
        });
    };

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
                                {t(locale, 'public.contact')}
                            </h1>
                            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
                                {t(locale, 'public.contactDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <Card className="border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.getInTouch')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
                                        <div>
                                            <label
                                                htmlFor="name"
                                                className="block text-sm font-medium text-card-foreground mb-1"
                                            >
                                                {t(locale, 'public.name')}
                                            </label>
                                            <input
                                                {...register('name', { required: t(locale, 'public.requiredField') })}
                                                type="text"
                                                id="name"
                                                className="w-full px-4 py-3 border border-input rounded-lg bg-input-background text-input-foreground focus:ring-2 focus:ring-input-focus focus:border-transparent"
                                                required
                                            />
                                            {errors.name && (
                                                <p className="mt-1 text-sm text-error">{errors.name.message}</p>
                                            )}
                                        </div>
                                        <div>
                                            <label
                                                htmlFor="email"
                                                className="block text-sm font-medium text-card-foreground mb-1"
                                            >
                                                {t(locale, 'public.email')}
                                            </label>
                                            <input
                                                {...register('email', {
                                                    required: t(locale, 'public.requiredField'),
                                                    pattern: {
                                                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                                                        message: t(locale, 'public.invalidEmail'),
                                                    },
                                                })}
                                                type="email"
                                                id="email"
                                                className="w-full px-4 py-3 border border-input rounded-lg bg-input-background text-input-foreground focus:ring-2 focus:ring-input-focus focus:border-transparent"
                                                required
                                            />
                                            {errors.email && (
                                                <p className="mt-1 text-sm text-error">{errors.email.message}</p>
                                            )}
                                        </div>
                                        <div>
                                            <label
                                                htmlFor="subject"
                                                className="block text-sm font-medium text-card-foreground mb-1"
                                            >
                                                {t(locale, 'public.subject')}
                                            </label>
                                            <input
                                                {...register('subject', {
                                                    required: t(locale, 'public.requiredField'),
                                                })}
                                                type="text"
                                                id="subject"
                                                className="w-full px-4 py-3 border border-input rounded-lg bg-input-background text-input-foreground focus:ring-2 focus:ring-input-focus focus:border-transparent"
                                                required
                                            />
                                            {errors.subject && (
                                                <p className="mt-1 text-sm text-error">{errors.subject.message}</p>
                                            )}
                                        </div>
                                        <div>
                                            <label
                                                htmlFor="message"
                                                className="block text-sm font-medium text-card-foreground mb-1"
                                            >
                                                {t(locale, 'public.message')}
                                            </label>
                                            <textarea
                                                {...register('message', {
                                                    required: t(locale, 'public.requiredField'),
                                                })}
                                                id="message"
                                                rows={4}
                                                className="w-full px-4 py-3 border border-input rounded-lg bg-input-background text-input-foreground focus:ring-2 focus:ring-input-focus focus:border-transparent"
                                                required
                                            />
                                            {errors.message && (
                                                <p className="mt-1 text-sm text-error">{errors.message.message}</p>
                                            )}
                                        </div>
                                        <Button type="submit" className="w-full" disabled={isSubmitting}>
                                            {isSubmitting
                                                ? t(locale, 'public.sending')
                                                : t(locale, 'public.sendMessage')}
                                        </Button>
                                    </form>
                                    {flash.success && (
                                        <div className="mt-4 p-4 rounded-lg bg-success/10 text-success">
                                            {flash.success}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.contactInformation')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        <div className="flex items-start gap-4">
                                            <MapPin className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    {t(locale, 'public.address')}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {t(locale, 'public.addressLine1')}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {t(locale, 'public.addressLine2')}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-4">
                                            <Phone className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    {t(locale, 'public.phone')}
                                                </p>
                                                <p className="text-muted-foreground dir-ltr">+966 50 123 4567</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-4">
                                            <Mail className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    {t(locale, 'public.email')}
                                                </p>
                                                <p className="text-muted-foreground">info@alnoor.school</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-4">
                                            <Clock className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    {t(locale, 'public.officeHours')}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {t(locale, 'public.officeHoursValue')}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
