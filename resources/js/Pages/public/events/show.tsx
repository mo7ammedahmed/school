import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, MapPin } from 'lucide-react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface Event {
    id: number;
    title: string;
    date: string;
    time: string;
    location: string;
    description: string;
    category: string;
    featured_image?: string;
}

interface EventsShowProps {
    event: Event;
}

export default function EventsShow({ event }: EventsShowProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/events">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t(locale, 'public.backToEvents')}
                            </Link>
                        </Button>

                        <div className="grid gap-6 lg:grid-cols-3">
                            <Card className="lg:col-span-2 border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                {event.featured_image && (
                                    <div className="relative h-64 w-full overflow-hidden rounded-t-lg">
                                        <img
                                            src={event.featured_image}
                                            alt={event.title}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                )}
                                <CardHeader>
                                    <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span className="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                            {event.category}
                                        </span>
                                    </div>
                                    <CardTitle className="text-3xl md:text-4xl">{event.title}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                        {event.description}
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle>{t(locale, 'public.information')}</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-start gap-3">
                                        <Calendar className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.date')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">{new Date(event.date).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <Clock className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.time')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">{event.time}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <MapPin className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.location')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">{event.location}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <div className="h-5 w-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.category')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">{event.category}</p>
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
