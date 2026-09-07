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

export default function OnboardingFeeStructure() {
    const { data, setData, post, processing } = useForm({
        fee_name: '',
        amount: '',
        grade_level_id: '',
        frequency: 'monthly',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/fee-structure');
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
            id: 'fee-name-step',
            text: [
                `<p>Enter the name of the fee (e.g., Tuition Fee, Activity Fee).</p>`
            ],
            attachTo: {
                element: '#fee_name',
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
            id: 'amount-step',
            text: [
                `<p>Enter the amount for the fee.</p>`,
                `<p>This is the cost per {{frequency}} for the selected grade level.</p>`
            ],
            attachTo: {
                element: '#amount',
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
            id: 'grade-level-step',
            text: [
                `<p>Select the grade level this fee applies to.</p>`
            ],
            attachTo: {
                element: '#grade_level_id',
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
            id: 'frequency-step',
            text: [
                `<p>Select how often this fee is charged.</p>`
            ],
            attachTo: {
                element: '#frequency',
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
                `<p>You've configured the fee structure.</p>`,
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
            title="Fee Structure Setup"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Fee Structure' },
            ]}
        >
            <PageHeader
                title="Fee Structure Setup"
                description="Configure basic fee structure"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Fee Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="fee_name">Fee Name</Label>
                                <Input id="fee_name" value={data.fee_name} onChange={(e) => setData('fee_name', e.target.value)} placeholder="e.g., Tuition Fee" required />
                            </div>
                            <div>
                                <Label htmlFor="amount">Amount</Label>
                                <Input id="amount" type="number" step="0.01" value={data.amount} onChange={(e) => setData('amount', e.target.value)} required />
                            </div>
                            <div>
                                <Label htmlFor="grade_level_id">Grade Level</Label>
                                <select id="grade_level_id" value={data.grade_level_id} onChange={(e) => setData('grade_level_id', e.target.value)} className="input" required>
                                    <option value="">Select grade level</option>
                                    <option value="1">Grade 1</option>
                                    <option value="2">Grade 2</option>
                                    <option value="3">Grade 3</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="frequency">Payment Frequency</Label>
                                <select id="frequency" value={data.frequency} onChange={(e) => setData('frequency', e.target.value)} className="input" required>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semester">Per Semester</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding/students"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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