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
    gradeLevel?: {
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
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-4xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/programs">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t(locale, 'public.backToPrograms')}
                            </Link>
                        </Button>

                        <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 overflow-hidden">
                            <CardHeader>
                                <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    {program.gradeLevel && (
                                        <span className="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                            {program.gradeLevel.name}
                                        </span>
                                    )}
                                </div>
                                <CardTitle className="text-3xl md:text-4xl">{program.name}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-8">
                                <div>
                                    <h3 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{t(locale, 'public.aboutProgram')}</h3>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                        {program.description || t(locale, 'public.noDescription')}
                                    </p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="flex items-center gap-3 p-4 bg-white dark:bg-gray-700 rounded-lg">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                            <BookOpen className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">{t(locale, 'public.programType')}</p>
                                            <p className="font-medium text-gray-900 dark:text-white">{program.gradeLevel?.name || t(locale, 'public.unknown')}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3 p-4 bg-white dark:bg-gray-700 rounded-lg">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                            <Users className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">{t(locale, 'public.gradeLevel')}</p>
                                            <p className="font-medium text-gray-900 dark:text-white">{program.gradeLevel?.level ? `Level ${program.gradeLevel.level}` : t(locale, 'public.unknown')}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
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