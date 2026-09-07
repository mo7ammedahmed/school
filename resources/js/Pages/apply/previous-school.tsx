import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';

export default function ApplyPreviousSchool() {
    const { data, setData, post, processing, errors } = useForm({
        school_name: '',
        school_address: '',
        last_grade_completed: '',
        reason_for_leaving: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/apply/previous-school');
    };

    return (
        <AppShell
            title="Previous School Information"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Previous School' },
            ]}
        >
            <PageHeader
                title="Previous School Information"
                description="Provide details about the student's previous school"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Previous School Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="school_name">School Name</Label>
                                <Input id="school_name" value={data.school_name} onChange={(e) => setData('school_name', e.target.value)} required />
                                {errors.school_name && <p className="text-red-600 text-sm mt-1">{errors.school_name}</p>}
                            </div>
                            <div>
                                <Label htmlFor="school_address">School Address</Label>
                                <Input id="school_address" value={data.school_address} onChange={(e) => setData('school_address', e.target.value)} required />
                                {errors.school_address && <p className="text-red-600 text-sm mt-1">{errors.school_address}</p>}
                            </div>
                            <div>
                                <Label htmlFor="last_grade_completed">Last Grade Completed</Label>
                                <Input id="last_grade_completed" value={data.last_grade_completed} onChange={(e) => setData('last_grade_completed', e.target.value)} required />
                                {errors.last_grade_completed && <p className="text-red-600 text-sm mt-1">{errors.last_grade_completed}</p>}
                            </div>
                            <div>
                                <Label htmlFor="reason_for_leaving">Reason for Leaving</Label>
                                <Input id="reason_for_leaving" value={data.reason_for_leaving} onChange={(e) => setData('reason_for_leaving', e.target.value)} required />
                                {errors.reason_for_leaving && <p className="text-red-600 text-sm mt-1">{errors.reason_for_leaving}</p>}
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/apply/student"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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
