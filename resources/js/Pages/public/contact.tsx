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

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<ContactForm>();

    const onSubmit = (data: ContactForm) => {
        router.post('/contact', data as unknown as RequestPayload, {
            onSuccess: () => reset(),
        });
    };

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.contact')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.contactDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.getInTouch')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
                                        <div>
                                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {t(locale, 'public.name')}
                                            </label>
                                            <input
                                                {...register('name', { required: t(locale, 'public.requiredField') })}
                                                type="text"
                                                id="name"
                                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                                required
                                            />
                                            {errors.name && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name.message}</p>}
                                        </div>
                                        <div>
                                            <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {t(locale, 'public.email')}
                                            </label>
                                            <input
                                                {...register('email', { 
                                                    required: t(locale, 'public.requiredField'),
                                                    pattern: { value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i, message: t(locale, 'public.invalidEmail') }
                                                })}
                                                type="email"
                                                id="email"
                                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                                required
                                            />
                                            {errors.email && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.email.message}</p>}
                                        </div>
                                        <div>
                                            <label htmlFor="subject" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {t(locale, 'public.subject')}
                                            </label>
                                            <input
                                                {...register('subject', { required: t(locale, 'public.requiredField') })}
                                                type="text"
                                                id="subject"
                                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                                required
                                            />
                                            {errors.subject && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.subject.message}</p>}
                                        </div>
                                        <div>
                                            <label htmlFor="message" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {t(locale, 'public.message')}
                                            </label>
                                            <textarea
                                                {...register('message', { required: t(locale, 'public.requiredField') })}
                                                id="message"
                                                rows={4}
                                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                                required
                                            />
                                            {errors.message && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.message.message}</p>}
                                        </div>
                                        <Button type="submit" className="w-full" disabled={isSubmitting}>
                                            {isSubmitting ? t(locale, 'public.sending') : t(locale, 'public.sendMessage')}
                                        </Button>
                                    </form>
                                    {flash.success && (
                                        <div className="mt-4 p-4 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            {flash.success}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.contactInformation')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        <div className="flex items-start gap-4">
                                            <MapPin className="h-6 w-6 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.address')}</p>
                                                <p className="text-gray-600 dark:text-gray-400">{t(locale, 'public.addressLine1')}</p>
                                                <p className="text-gray-600 dark:text-gray-400">{t(locale, 'public.addressLine2')}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-4">
                                            <Phone className="h-6 w-6 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.phone')}</p>
                                                <p className="text-gray-600 dark:text-gray-400 dir-ltr">+966 50 123 4567</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-4">
                                            <Mail className="h-6 w-6 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.email')}</p>
                                                <p className="text-gray-600 dark:text-gray-400">info@alnoor.school</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-4">
                                            <Clock className="h-6 w-6 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.officeHours')}</p>
                                                <p className="text-gray-600 dark:text-gray-400">{t(locale, 'public.officeHoursValue')}</p>
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
