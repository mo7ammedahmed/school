import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Users } from 'lucide-react';
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

interface ProgramsShowProps {
    program: Program;
}

export default function ProgramsShow({ program }: ProgramsShowProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-4xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/programs">
                                <ArrowLeft className="me-2 h-4 w-4" />
                                {t(locale, 'public.backToPrograms')}
                            </Link>
                        </Button>

                        <Card className="border-0 shadow-lg bg-card text-card-foreground overflow-hidden">
                            <CardHeader>
                                <div className="flex items-center gap-2 text-sm text-muted-foreground mb-4">
                                    {program.grade_level && (
                                        <span className="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary dark:text-pine-200">
                                            {program.grade_level.name}
                                        </span>
                                    )}
                                </div>
                                <CardTitle className="text-3xl md:text-4xl">{program.name}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-8">
                                <div>
                                    <h3 className="mb-4 text-xl font-semibold text-foreground">
                                        {t(locale, 'public.aboutProgram')}
                                    </h3>
                                    <p className="text-card-foreground leading-relaxed whitespace-pre-line">
                                        {program.description || t(locale, 'public.noDescription')}
                                    </p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="flex items-center gap-3 p-4 bg-secondary text-secondary-foreground rounded-lg">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-pine-200">
                                            <BookOpen className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                {t(locale, 'public.programType')}
                                            </p>
                                            <p className="font-medium text-foreground">
                                                {program.grade_level?.name || t(locale, 'public.unknown')}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3 p-4 bg-secondary text-secondary-foreground rounded-lg">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-pine-200">
                                            <Users className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                {t(locale, 'public.gradeLevel')}
                                            </p>
                                            <p className="font-medium text-foreground">
                                                {program.grade_level?.level
                                                    ? t(locale, 'public.level', { level: program.grade_level.level })
                                                    : t(locale, 'public.unknown')}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-border">
                                    <Button asChild className="w-full md:w-auto">
                                        <Link href="/apply">{t(locale, 'public.applyNow')}</Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
