import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';

export default function ApplyGuardian() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        relationship: '',
        occupation: '',
        address: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/apply/guardian');
    };

    return (
        <AppShell
            title="Guardian Information"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Guardian Information' },
            ]}
        >
            <PageHeader
                title="Guardian Information"
                description="Enter the guardian's details"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Guardian Details</CardTitle>
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
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                                {errors.email && <p className="text-red-600 text-sm mt-1">{errors.email}</p>}
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" type="tel" value={data.phone} onChange={(e) => setData('phone', e.target.value)} required />
                                {errors.phone && <p className="text-red-600 text-sm mt-1">{errors.phone}</p>}
                            </div>
                            <div>
                                <Label htmlFor="relationship">Relationship</Label>
                                <select id="relationship" value={data.relationship} onChange={(e) => setData('relationship', e.target.value)} className="input" required>
                                    <option value="">Select relationship</option>
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="guardian">Guardian</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.relationship && <p className="text-red-600 text-sm mt-1">{errors.relationship}</p>}
                            </div>
                            <div>
                                <Label htmlFor="occupation">Occupation</Label>
                                <Input id="occupation" value={data.occupation} onChange={(e) => setData('occupation', e.target.value)} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} required />
                                {errors.address && <p className="text-red-600 text-sm mt-1">{errors.address}</p>}
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/apply/start"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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
