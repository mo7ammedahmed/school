import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';

export default function ApplyStart() {
    const { data, setData, post, processing, errors } = useForm({
        academic_year_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/apply/start');
    };

    return (
        <AppShell
            title="Start Application"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Start Application' },
            ]}
        >
            <PageHeader
                title="Start Application"
                description="Select the academic year for your application"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Academic Year Selection</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label htmlFor="academic_year_id" className="block text-sm font-medium mb-1">
                                Academic Year
                            </label>
                            <select
                                id="academic_year_id"
                                value={data.academic_year_id}
                                onChange={(e) => setData('academic_year_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                required
                            >
                                <option value="">Select academic year</option>
                                <option value="1">2024-2025</option>
                                <option value="2">2025-2026</option>
                            </select>
                            {errors.academic_year_id && (
                                <p className="text-red-600 text-sm mt-1">{errors.academic_year_id}</p>
                            )}
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/apply"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                Continue<ArrowRight className="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
