import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { User } from 'lucide-react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface Teacher {
    id: number;
    first_name: string;
    last_name: string;
    /** The profile calls this `specialization`; `position` was never a column. */
    specialization: string | null;
    bio: string | null;
    subjects?: { id: number; name: string }[];
}

interface TeachersProps {
    teachers: Teacher[];
}

export default function Teachers({ teachers }: TeachersProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
                                {t(locale, 'public.teachers')}
                            </h1>
                            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
                                {t(locale, 'public.teachersDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {teachers.map((teacher) => (
                                <Card
                                    key={teacher.id}
                                    className="flex flex-col border-0 shadow-lg bg-card text-card-foreground hover:shadow-xl transition-shadow duration-300"
                                >
                                    <CardHeader className="flex-1">
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary dark:text-pine-200">
                                                <User className="h-8 w-8" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-lg">
                                                    {teacher.first_name} {teacher.last_name}
                                                </CardTitle>
                                                <p className="text-sm text-primary dark:text-pine-200 font-medium">
                                                    {teacher.specialization}
                                                </p>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="flex flex-1 flex-col justify-between gap-4">
                                        <p className="text-sm text-muted-foreground line-clamp-3">{teacher.bio}</p>
                                        <Button asChild variant="outline" className="w-full">
                                            <Link href={`/faculty/${teacher.id}`}>
                                                {t(locale, 'public.viewProfile')}
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
