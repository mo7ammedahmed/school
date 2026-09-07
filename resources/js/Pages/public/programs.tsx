import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface Program {
    id: number;
    name: string;
    description: string;
    grade_level_id: number;
    gradeLevel?: {
        id: number;
        name: string;
        level: number;
    };
}

interface ProgramsProps {
    programs: Program[];
}

export default function Programs({ programs }: ProgramsProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.programs')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.programsDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {programs.map((program) => (
                                <Card key={program.id} className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 hover:shadow-xl transition-shadow duration-300">
                                    <CardHeader>
                                        <CardTitle className="text-xl">{program.name}</CardTitle>
                                        {program.gradeLevel && (
                                            <p className="text-sm text-emerald-600 dark:text-emerald-400 font-medium mt-1">
                                                {program.gradeLevel.name}
                                            </p>
                                        )}
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-gray-600 dark:text-gray-300 mb-6">{program.description || t(locale, 'public.noDescription')}</p>
                                        <Button variant="outline" asChild className="w-full">
                                            <Link href={`/programs/${program.id}`}>{t(locale, 'public.learnMore')}</Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <div className="mt-12 text-center">
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
