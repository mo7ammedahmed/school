import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';

export default function ApplyDocuments() {
    const { setData, post, processing, errors } = useForm({
        birth_certificate: null as File | null,
        previous_school_records: null as File | null,
        passport_photos: null as File | null,
        medical_records: null as File | null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>, field: string) => {
        if (e.target.files && e.target.files[0]) {
            setData(field as any, e.target.files[0]);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/apply/documents');
    };

    return (
        <AppShell
            title="Upload Documents"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Documents' },
            ]}
        >
            <PageHeader
                title="Upload Documents"
                description="Upload required documents for your application"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Required Documents</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="birth_certificate">Birth Certificate</Label>
                                <Input
                                    id="birth_certificate"
                                    type="file"
                                    onChange={(e) => handleFileChange(e, 'birth_certificate')}
                                    required
                                />
                                {errors.birth_certificate && <p className="text-red-600 text-sm mt-1">{errors.birth_certificate}</p>}
                            </div>

                            <div>
                                <Label htmlFor="previous_school_records">Previous School Records</Label>
                                <Input
                                    id="previous_school_records"
                                    type="file"
                                    onChange={(e) => handleFileChange(e, 'previous_school_records')}
                                    required
                                />
                                {errors.previous_school_records && <p className="text-red-600 text-sm mt-1">{errors.previous_school_records}</p>}
                            </div>

                            <div>
                                <Label htmlFor="passport_photos">Passport Photos</Label>
                                <Input
                                    id="passport_photos"
                                    type="file"
                                    onChange={(e) => handleFileChange(e, 'passport_photos')}
                                    required
                                />
                                {errors.passport_photos && <p className="text-red-600 text-sm mt-1">{errors.passport_photos}</p>}
                            </div>

                            <div>
                                <Label htmlFor="medical_records">Medical Records</Label>
                                <Input
                                    id="medical_records"
                                    type="file"
                                    onChange={(e) => handleFileChange(e, 'medical_records')}
                                    required
                                />
                                {errors.medical_records && <p className="text-red-600 text-sm mt-1">{errors.medical_records}</p>}
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/apply/previous-school"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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
