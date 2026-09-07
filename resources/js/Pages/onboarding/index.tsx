import type { FormEvent } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowRight } from 'lucide-react';
import { useForm } from '@inertiajs/react';

export default function OnboardingIndex() {
    const { data, setData, post, processing } = useForm({
        school_name: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/school-information');
    };

    return (
        <AppShell
            title="Onboarding"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding' },
            ]}
        >
            <PageHeader
                title="Welcome to School Management System"
                description="Let's set up your school"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Get Started</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <Label htmlFor="school_name">School Name</Label>
                            <Input
                                id="school_name"
                                value={data.school_name}
                                onChange={(e) => setData('school_name', e.target.value)}
                                placeholder="Enter your school name"
                                required
                            />
                        </div>

                        <Button type="submit" disabled={processing} className="w-full">
                            Continue<ArrowRight className="ml-2 h-4 w-4" />
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
