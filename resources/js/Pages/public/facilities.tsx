import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { t, tk } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

import {
    Presentation,
    FlaskConical,
    Library,
    Dumbbell,
    Drama,
    Trees,
    Wifi,
    Users,
} from 'lucide-react';

interface Facility {
    title: string;
    description: string;
    icon: string;
}

interface FacilitiesProps {
    facilities: Facility[];
}

const facilityIcons: Record<string, React.ComponentType<{ className?: string }>> = {
    Presentation,
    FlaskConical,
    Library,
    Dumbbell,
    Drama,
    Trees,
    Wifi,
    Users,
};

export default function Facilities({ facilities }: FacilitiesProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.facilities')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.facilitiesDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-16">
                            {facilities.map((facility) => {
                                const Icon = facilityIcons[facility.icon] ?? Presentation;
                                return (
                                    <Card key={facility.title} className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 hover:shadow-xl transition-shadow duration-300">
                                        <CardHeader>
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                                    <Icon className="h-5 w-5" />
                                                </div>
                                                <CardTitle>{tk(locale, facility.title)}</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="text-gray-600 dark:text-gray-300">{tk(locale, facility.description)}</p>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>

                        <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                            <CardHeader>
                                <CardTitle className="text-2xl">{t(locale, 'public.campusHighlights')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="flex items-center gap-3">
                                        <Wifi className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-gray-700 dark:text-gray-300">{t(locale, 'public.highlights.wifi')}</span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Users className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-gray-700 dark:text-gray-300">{t(locale, 'public.highlights.security')}</span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Trees className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-gray-700 dark:text-gray-300">{t(locale, 'public.highlights.ecoFriendly')}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
