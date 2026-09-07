import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

export default function OnboardingAcademicYear() {
    const { data, setData, post, processing } = useForm({
        name: '',
        start_date: '',
        end_date: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/academic-year');
    };

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
            id: 'name-step',
            text: [
                `<p>Enter the name of the academic year (e.g., 2024-2025).</p>`
            ],
            attachTo: {
                element: '#name',
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
            id: 'start-date-step',
            text: [
                `<p>Select the start date of the academic year.</p>`
            ],
            attachTo: {
                element: '#start_date',
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
            id: 'end-date-step',
            text: [
                `<p>Select the end date of the academic year.</p>`
            ],
            attachTo: {
                element: '#end_date',
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
                `<p>You've configured the academic year.</p>`,
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
            title="Academic Year Setup"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Academic Year' },
            ]}
        >
            <PageHeader
                title="Academic Year Setup"
                description="Configure the academic year"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Academic Year Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Academic Year Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g., 2024-2025" required />
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input id="start_date" type="date" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input id="end_date" type="date" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} required />
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding/create-school"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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