import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

export default function About() {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
                                {t(locale, 'public.about')}
                            </h1>
                            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
                                {t(locale, 'public.aboutDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 mb-16">
                            <Card className="border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.mission')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground text-lg leading-relaxed">
                                        {t(locale, 'public.missionDescription')}
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.vision')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground text-lg leading-relaxed">
                                        {t(locale, 'public.visionDescription')}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <Card className="border-0 shadow-lg bg-card text-card-foreground mb-16">
                            <CardHeader>
                                <CardTitle className="text-2xl">{t(locale, 'public.whyChooseUs')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-4">
                                    <li className="flex items-start gap-3">
                                        <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                        <span className="text-card-foreground text-lg">
                                            {t(locale, 'public.experiencedFaculty')}
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                        <span className="text-card-foreground text-lg">
                                            {t(locale, 'public.stateOfArtFacilities')}
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                        <span className="text-card-foreground text-lg">
                                            {t(locale, 'public.comprehensiveCurriculum')}
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                        <span className="text-card-foreground text-lg">
                                            {t(locale, 'public.safeEnvironment')}
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                        <span className="text-card-foreground text-lg">
                                            {t(locale, 'public.extracurricularFocus')}
                                        </span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>

                        <div className="text-center">
                            <Button size="lg" asChild className="text-lg px-8 py-6">
                                <Link href="/apply">{t(locale, 'public.applyNow')}</Link>
                            </Button>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
