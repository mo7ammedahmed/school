import type { FormEvent } from 'react';
import { useEffect } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

export default function OnboardingCreateSchool() {
    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        window.location.href = '/onboarding/academic-year';
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
            id: 'info-step',
            text: [
                `<p>This page creates your school profile and sets up the basic structure.</p>`,
                `<p>You can always modify these settings later from the school settings.</p>`
            ],
            attachTo: {
                element: '.p-4.bg-blue-50',
                on: 'top'
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
            id: 'create-school-step',
            text: [
                `<p>Click 'Create School' to proceed to the next step.</p>`
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
            title="Create School"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Create School' },
            ]}
        >
            <PageHeader
                title="Create School"
                description="Set up your school profile"
            />

            <Card>
                <CardHeader>
                    <CardTitle>School Profile Setup</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p className="text-sm text-blue-800">
                                This will create your school profile and set up the basic structure. You can always
                                modify these settings later.
                            </p>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding/school-information"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                            </Button>
                            <Button type="submit">
                                Create School<ArrowRight className="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}