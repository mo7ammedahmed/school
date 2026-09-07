import type { FormEvent } from 'react';
import { useEffect } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

export default function OnboardingSchoolInformation() {
    const { data, setData, post, processing } = useForm({
        address: '',
        phone: '',
        email: '',
        website: '',
        principal_name: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/create-school');
    };

    // Initialize Shepherd tour
    useEffect(() => {
        const tour = new Shepherd.Tour({
            defaultStepOptions: {
                cancelIcon: {
                    enabled: true
                },
                classes: 'shadow-md bg-purple-dark',
                scrollTo: { behavior: 'smooth', block: 'center' }
            },
            useModalOverlay: true
        });

        tour.addStep({
            id: 'address-step',
            text: [
                `<p>Enter your school's address.</p>`,
                `<p>This will be used for official communications and reports.</p>`
            ],
            attachTo: {
                element: '#address',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });

        tour.addStep({
            id: 'phone-step',
            text: [
                `<p>Enter your school's phone number.</p>`,
                `<p>This will be displayed on the website and used for contact.</p>`
            ],
            attachTo: {
                element: '#phone',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });

        tour.addStep({
            id: 'email-step',
            text: [
                `<p>Enter your school's email address.</p>`,
                `<p>This will be used for official correspondence.</p>`
            ],
            attachTo: {
                element: '#email',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });

        tour.addStep({
            id: 'website-step',
            text: [
                `<p>Enter your school's website (optional).</p>`,
                `<p>This will be displayed on the website footer.</p>`
            ],
            attachTo: {
                element: '#website',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });

        tour.addStep({
            id: 'principal-step',
            text: [
                `<p>Enter the name of the school principal.</p>`,
                `<p>This will be used in official documents and reports.</p>`
            ],
            attachTo: {
                element: '#principal_name',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });

        tour.addStep({
            id: 'finish-step',
            text: [
                `<p>You've entered all the required school information.</p>`,
                `<p>Click 'Continue' to proceed to the next step.</p>`
            ],
            attachTo: {
                element: 'button[type="submit"]',
                on: 'top'
            },
            buttons: [
                {
                    text: 'Back',
                    action: tour.back
                },
                {
                    text: 'Exit',
                    action: () => tour.complete()
                }
            ]
        });

        // Start the tour
        tour.start();

        // Clean up on unmount
        return () => {
            tour.complete();
        };
    }, []);

    return (
        <AppShell
            title="School Information"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'School Information' },
            ]}
        >
            <PageHeader
                title="School Information"
                description="Provide basic information about your school"
            />

            <Card>
                <CardHeader>
                    <CardTitle>School Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" type="tel" value={data.phone} onChange={(e) => setData('phone', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="website">Website</Label>
                                <Input id="website" type="url" value={data.website} onChange={(e) => setData('website', e.target.value)} />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="principal_name">Principal Name</Label>
                                <Input id="principal_name" value={data.principal_name} onChange={(e) => setData('principal_name', e.target.value)} required />
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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