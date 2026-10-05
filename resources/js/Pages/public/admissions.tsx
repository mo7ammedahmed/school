import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Phone, Mail, Clock } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface AdmissionPeriod {
    id: number;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string;
}

interface AdmissionsProps {
    periods: AdmissionPeriod[];
}

export default function Admissions({ periods }: AdmissionsProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
                                {t(locale, 'public.admissions')}
                            </h1>
                            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
                                {t(locale, 'public.admissionsDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 mb-12">
                            <Card className="border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.admissionProcess')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ol className="space-y-4 list-decimal list-inside">
                                        <li className="text-card-foreground text-lg">
                                            {t(locale, 'public.admissions.step1')}
                                        </li>
                                        <li className="text-card-foreground text-lg">
                                            {t(locale, 'public.admissions.step2')}
                                        </li>
                                        <li className="text-card-foreground text-lg">
                                            {t(locale, 'public.admissions.step3')}
                                        </li>
                                        <li className="text-card-foreground text-lg">
                                            {t(locale, 'public.admissions.step4')}
                                        </li>
                                        <li className="text-card-foreground text-lg">
                                            {t(locale, 'public.admissions.step5')}
                                        </li>
                                        <li className="text-card-foreground text-lg">
                                            {t(locale, 'public.admissions.step6')}
                                        </li>
                                    </ol>
                                </CardContent>
                            </Card>

                            <Card className="border-0 shadow-lg bg-card text-card-foreground">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.requiredDocuments')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <span className="text-card-foreground text-lg">
                                                {t(locale, 'public.documents.birthCertificate')}
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <span className="text-card-foreground text-lg">
                                                {t(locale, 'public.documents.previousRecords')}
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <span className="text-card-foreground text-lg">
                                                {t(locale, 'public.documents.photos')}
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <span className="text-card-foreground text-lg">
                                                {t(locale, 'public.documents.guardianId')}
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-primary dark:text-pine-200 mt-0.5 flex-shrink-0" />
                                            <span className="text-card-foreground text-lg">
                                                {t(locale, 'public.documents.medicalRecords')}
                                            </span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>

                        {periods.length > 0 && (
                            <Card className="border-0 shadow-lg bg-card text-card-foreground mb-12">
                                <CardHeader>
                                    <CardTitle className="text-2xl">
                                        {t(locale, 'public.activeAdmissionPeriods')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {periods.map((period) => (
                                            <div
                                                key={period.id}
                                                className="p-6 bg-secondary text-secondary-foreground rounded-lg border border-border"
                                            >
                                                <h3 className="text-xl font-semibold text-foreground mb-2">
                                                    {period.name}
                                                </h3>
                                                <p className="text-muted-foreground mb-4">{period.description}</p>
                                                <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                    <span>
                                                        {t(locale, 'public.period.dates', {
                                                            start: period.start_date,
                                                            end: period.end_date,
                                                        })}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card className="border-0 shadow-lg bg-card text-card-foreground mb-12">
                            <CardHeader>
                                <CardTitle className="text-2xl">
                                    {t(locale, 'public.contactAdmissionsOffice')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="flex items-center gap-3">
                                        <Phone className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium text-foreground">{t(locale, 'public.phone')}</p>
                                            <p className="text-sm text-muted-foreground">+966 50 123 4567</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Mail className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium text-foreground">{t(locale, 'public.email')}</p>
                                            <p className="text-sm text-muted-foreground">admissions@alnoor.school</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Clock className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="font-medium text-foreground">
                                                {t(locale, 'public.officeHours')}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {t(locale, 'public.officeHoursValue')}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="text-center">
                            <Button size="lg" asChild className="text-lg px-8 py-6">
                                <Link href="/apply">{t(locale, 'public.startApplication')}</Link>
                            </Button>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
