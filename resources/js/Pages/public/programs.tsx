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
    /** Relations arrive snake-cased, which is why the page read `undefined`. */
    grade_level?: {
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
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
                                {t(locale, 'public.programs')}
                            </h1>
                            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
                                {t(locale, 'public.programsDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {programs.map((program) => (
                                <Card
                                    key={program.id}
                                    className="border-0 shadow-lg bg-card text-card-foreground hover:shadow-xl transition-shadow duration-300"
                                >
                                    <CardHeader>
                                        <CardTitle className="text-xl">{program.name}</CardTitle>
                                        {program.grade_level && (
                                            <p className="text-sm text-primary dark:text-pine-200 font-medium mt-1">
                                                {program.grade_level.name}
                                            </p>
                                        )}
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground mb-6">
                                            {program.description || t(locale, 'public.noDescription')}
                                        </p>
                                        <Button variant="outline" asChild className="w-full">
                                            <Link href={`/programs/${program.id}`}>
                                                {t(locale, 'public.learnMore')}
                                            </Link>
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
