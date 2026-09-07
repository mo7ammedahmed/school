import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Calendar, Clock, MapPin, ArrowLeft } from 'lucide-react';
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

interface EventsIndexProps {
    events: {
        data: Event[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function EventsIndex({ events }: EventsIndexProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.events')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.eventsDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-12">
                            {events.data.map((event) => (
                                <Card key={event.id} className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col">
                                    {event.featured_image && (
                                        <div className="relative h-48 w-full overflow-hidden">
                                            <img
                                                src={event.featured_image}
                                                alt={event.title}
                                                className="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                                            />
                                        </div>
                                    )}
                                    <CardHeader>
                                        <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span className="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                {event.category}
                                            </span>
                                        </div>
                                        <CardTitle className="text-xl line-clamp-2">{event.title}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="flex-1 flex flex-col">
                                        <div className="space-y-2 mb-4">
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <Calendar className="h-4 w-4" />
                                                <span>{new Date(event.date).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-US', {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}</span>
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <Clock className="h-4 w-4" />
                                                <span>{event.time}</span>
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <MapPin className="h-4 w-4" />
                                                <span>{event.location}</span>
                                            </div>
                                        </div>
                                        <p className="text-gray-600 dark:text-gray-300 line-clamp-3 mb-4 flex-1">{event.description}</p>
                                        <Button asChild variant="outline" className="w-full mt-auto">
                                            <Link href={`/events/${event.id}`}>{t(locale, 'public.viewDetails')}</Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Pagination */}
                        {events.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {events.current_page > 1 && (
                                    <Button variant="outline" asChild>
                                        <Link href={`/events?page=${events.current_page - 1}`}>
                                            <ArrowLeft className="mr-2 h-4 w-4" />
                                            {t(locale, 'public.previous')}
                                        </Link>
                                    </Button>
                                )}
                                <span className="text-gray-600 dark:text-gray-400 px-4">
                                    {t(locale, 'public.page', { current: events.current_page, total: events.last_page })}
                                </span>
                                {events.current_page < events.last_page && (
                                    <Button variant="outline" asChild>
                                        <Link href={`/events?page=${events.current_page + 1}`}>
                                            {t(locale, 'public.next')}
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}