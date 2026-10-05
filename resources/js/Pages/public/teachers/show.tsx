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
    /** `position` and `education` were never columns; these are. */
    specialization: string | null;
    qualification: string | null;
    bio: string | null;
    email: string | null;
    phone: string | null;
    /** Objects, not strings: the page rendered them as React children. */
    subjects: { id: number; name: string }[];
}

interface TeachersShowProps {
    teacher: Teacher;
}

export default function TeachersShow({ teacher }: TeachersShowProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/faculty">
                                <ArrowLeft className="me-2 h-4 w-4" />
                                {t(locale, 'public.backToTeachers')}
                            </Link>
                        </Button>

                        <div className="grid gap-6 lg:grid-cols-3">
                            <Card className="lg:col-span-1 border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <div className="flex flex-col items-center text-center">
                                        <div className="flex h-32 w-32 items-center justify-center rounded-full bg-primary/10 text-primary dark:text-pine-200">
                                            <User className="h-16 w-16" />
                                        </div>
                                        <CardTitle className="mt-4 text-2xl">
                                            {teacher.first_name} {teacher.last_name}
                                        </CardTitle>
                                        <p className="text-primary dark:text-pine-200 font-medium">
                                            {teacher.specialization}
                                        </p>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-3">
                                            <Mail className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <p className="text-sm font-medium text-foreground">
                                                    {t(locale, 'public.email')}
                                                </p>
                                                <p className="text-sm text-muted-foreground">{teacher.email}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Phone className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <p className="text-sm font-medium text-foreground">
                                                    {t(locale, 'public.phone')}
                                                </p>
                                                <p className="text-sm text-muted-foreground">{teacher.phone}</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="lg:col-span-2 border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle>{t(locale, 'public.about')}</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">
                                            {t(locale, 'public.biography')}
                                        </h3>
                                        <p className="text-muted-foreground">{teacher.bio}</p>
                                    </div>

                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">
                                            {t(locale, 'public.education')}
                                        </h3>
                                        <p className="text-muted-foreground">{teacher.qualification}</p>
                                    </div>

                                    <div>
                                        <h3 className="mb-3 font-semibold text-foreground">
                                            {t(locale, 'public.subjectsTaught')}
                                        </h3>
                                        <div className="flex flex-wrap gap-2">
                                            {teacher.subjects.map((subject) => (
                                                <span
                                                    key={subject.id}
                                                    className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-sm text-primary dark:text-pine-200"
                                                >
                                                    <BookOpen className="h-3.5 w-3.5" />
                                                    {subject.name}
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
