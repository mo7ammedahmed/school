import { useEffect } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

export default function OnboardingFinish() {
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
            id: 'next-steps-step',
            text: [
                `<p>Here are some suggested next steps to get started with your school management system.</p>`
            ],
            attachTo: {
                element: '.p-4.bg-gray-50.rounded-lg.text-left:first-child',
                on: 'top'
            },
            buttons: [
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });

        tour.addStep({
            id: 'quick-actions-step',
            text: [
                `<p>Here are some quick actions you can take right now.</p>`
            ],
            attachTo: {
                element: '.p-4.bg-gray-50.rounded-lg.text-left:last-child',
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
            id: 'finish-step',
            text: [
                `<p>Click 'Go to Dashboard' to start using your school management system.</p>`
            ],
            attachTo: {
                element: 'button asChild > Link',
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
            title="Setup Complete"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Finish' },
            ]}
        >
            <PageHeader
                title="Setup Complete!"
                description="Your school management system is ready"
            />

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CheckCircle className="h-6 w-6 text-green-600" />
                        Congratulations!
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-8">
                        <CheckCircle className="h-16 w-16 text-green-600 mx-auto mb-4" />
                        <h2 className="text-2xl font-bold mb-2">Setup Complete!</h2>
                        <p className="text-gray-600 mb-6">
                            Your school management system has been successfully configured. You can now start
                            managing your school operations.
                        </p>

                        <div className="grid gap-4 md:grid-cols-2 mb-6">
                            <div className="p-4 bg-gray-50 rounded-lg text-left">
                                <h3 className="font-medium mb-2">Next Steps</h3>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Add more students and teachers</li>
                                    <li>• Create class sections</li>
                                    <li>• Set up timetable</li>
                                    <li>• Configure payment gateways</li>
                                </ul>
                            </div>
                            <div className="p-4 bg-gray-50 rounded-lg text-left">
                                <h3 className="font-medium mb-2">Quick Actions</h3>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• View Dashboard</li>
                                    <li>• Add Students</li>
                                    <li>• Create Classes</li>
                                    <li>• Manage Finance</li>
                                </ul>
                            </div>
                        </div>

                        <Button asChild>
                            <Link href="/dashboard">Go to Dashboard<ArrowRight className="ml-2 h-4 w-4" /></Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}