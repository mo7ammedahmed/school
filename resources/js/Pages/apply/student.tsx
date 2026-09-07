import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';

export default function ApplyStudent() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        date_of_birth: '',
        gender: '',
        nationality: '',
        address: '',
        previous_school: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/apply/student');
    };

    return (
        <AppShell
            title="Student Information"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Student Information' },
            ]}
        >
            <PageHeader
                title="Student Information"
                description="Enter the student's details"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Student Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="first_name">First Name</Label>
                                <Input id="first_name" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} required />
                                {errors.first_name && <p className="text-red-600 text-sm mt-1">{errors.first_name}</p>}
                            </div>
                            <div>
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input id="last_name" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} required />
                                {errors.last_name && <p className="text-red-600 text-sm mt-1">{errors.last_name}</p>}
                            </div>
                            <div>
                                <Label htmlFor="date_of_birth">Date of Birth</Label>
                                <Input id="date_of_birth" type="date" value={data.date_of_birth} onChange={(e) => setData('date_of_birth', e.target.value)} required />
                                {errors.date_of_birth && <p className="text-red-600 text-sm mt-1">{errors.date_of_birth}</p>}
                            </div>
                            <div>
                                <Label htmlFor="gender">Gender</Label>
                                <select id="gender" value={data.gender} onChange={(e) => setData('gender', e.target.value)} className="input" required>
                                    <option value="">Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.gender && <p className="text-red-600 text-sm mt-1">{errors.gender}</p>}
                            </div>
                            <div>
                                <Label htmlFor="nationality">Nationality</Label>
                                <Input id="nationality" value={data.nationality} onChange={(e) => setData('nationality', e.target.value)} required />
                                {errors.nationality && <p className="text-red-600 text-sm mt-1">{errors.nationality}</p>}
                            </div>
                            <div>
                                <Label htmlFor="previous_school">Previous School</Label>
                                <Input id="previous_school" value={data.previous_school} onChange={(e) => setData('previous_school', e.target.value)} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} required />
                                {errors.address && <p className="text-red-600 text-sm mt-1">{errors.address}</p>}
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/apply/guardian"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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
