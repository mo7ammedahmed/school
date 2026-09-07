import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { User, Mail, Phone, BookOpen, ArrowLeft } from 'lucide-react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface Teacher {
    id: number;
    first_name: string;
    last_name: string;
    position: string;
    bio: string;
    email: string;
    phone: string;
    education: string;
    subjects: string[];
}

interface TeachersShowProps {
    teacher: Teacher;
}

export default function TeachersShow({ teacher }: TeachersShowProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/teachers">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t(locale, 'public.backToTeachers')}
                            </Link>
                        </Button>

                        <div className="grid gap-6 lg:grid-cols-3">
                            <Card className="lg:col-span-1 border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <div className="flex flex-col items-center text-center">
                                        <div className="flex h-32 w-32 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                            <User className="h-16 w-16" />
                                        </div>
                                        <CardTitle className="mt-4 text-2xl">{teacher.first_name} {teacher.last_name}</CardTitle>
                                        <p className="text-emerald-600 dark:text-emerald-400 font-medium">{teacher.position}</p>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-3">
                                            <Mail className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">{t(locale, 'public.email')}</p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">{teacher.email}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Phone className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">{t(locale, 'public.phone')}</p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">{teacher.phone}</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="lg:col-span-2 border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle>{t(locale, 'public.about')}</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div>
                                        <h3 className="mb-2 font-semibold text-gray-900 dark:text-white">{t(locale, 'public.biography')}</h3>
                                        <p className="text-gray-600 dark:text-gray-300">{teacher.bio}</p>
                                    </div>

                                    <div>
                                        <h3 className="mb-2 font-semibold text-gray-900 dark:text-white">{t(locale, 'public.education')}</h3>
                                        <p className="text-gray-600 dark:text-gray-300">{teacher.education}</p>
                                    </div>

                                    <div>
                                        <h3 className="mb-3 font-semibold text-gray-900 dark:text-white">{t(locale, 'public.subjectsTaught')}</h3>
                                        <div className="flex flex-wrap gap-2">
                                            {teacher.subjects.map((subject: string) => (
                                                <span
                                                    key={subject}
                                                    className="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1 text-sm text-emerald-600 dark:text-emerald-400"
                                                >
                                                    <BookOpen className="h-3.5 w-3.5" />
                                                    {subject}
                                                </span>
                                            ))}
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
