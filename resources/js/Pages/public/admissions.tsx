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
    description: string;
    start_date: string;
    end_date: string;
    grade_levels: string;
}

interface AdmissionsProps {
    periods: AdmissionPeriod[];
}

export default function Admissions({ periods }: AdmissionsProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.admissions')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.admissionsDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 mb-12">
                            <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.admissionProcess')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ol className="space-y-4 list-decimal list-inside">
                                        <li className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.admissions.step1')}</li>
                                        <li className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.admissions.step2')}</li>
                                        <li className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.admissions.step3')}</li>
                                        <li className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.admissions.step4')}</li>
                                        <li className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.admissions.step5')}</li>
                                        <li className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.admissions.step6')}</li>
                                    </ol>
                                </CardContent>
                            </Card>

                            <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.requiredDocuments')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-emerald-600 mt-0.5 flex-shrink-0" />
                                            <span className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.documents.birthCertificate')}</span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-emerald-600 mt-0.5 flex-shrink-0" />
                                            <span className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.documents.previousRecords')}</span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-emerald-600 mt-0.5 flex-shrink-0" />
                                            <span className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.documents.photos')}</span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-emerald-600 mt-0.5 flex-shrink-0" />
                                            <span className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.documents.guardianId')}</span>
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <CheckCircle className="h-6 w-6 text-emerald-600 mt-0.5 flex-shrink-0" />
                                            <span className="text-gray-700 dark:text-gray-300 text-lg">{t(locale, 'public.documents.medicalRecords')}</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>

                        {periods.length > 0 && (
                            <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 mb-12">
                                <CardHeader>
                                    <CardTitle className="text-2xl">{t(locale, 'public.activeAdmissionPeriods')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {periods.map((period) => (
                                            <div key={period.id} className="p-6 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                                <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">{period.name}</h3>
                                                <p className="text-gray-600 dark:text-gray-300 mb-4">{period.description}</p>
                                                <div className="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                    <span>{t(locale, 'public.period.dates', { start: period.start_date, end: period.end_date })}</span>
                                                    <span>{t(locale, 'public.period.grades', { grades: period.grade_levels })}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 mb-12">
                            <CardHeader>
                                <CardTitle className="text-2xl">{t(locale, 'public.contactAdmissionsOffice')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="flex items-center gap-3">
                                        <Phone className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.phone')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">+966 50 123 4567</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Mail className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.email')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">admissions@alnoor.school</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Clock className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{t(locale, 'public.officeHours')}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">{t(locale, 'public.officeHoursValue')}</p>
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
