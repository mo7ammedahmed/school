import type { FormEvent } from 'react';
import { useEffect } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight, Plus } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

export default function OnboardingGrades() {
    const { data, setData, post, processing } = useForm({
        grades: [{ name: '', level: '' }],
    });

    const addGrade = () => {
        setData('grades', [...data.grades, { name: '', level: '' }]);
    };

    const removeGrade = (index: number) => {
        setData('grades', data.grades.filter((_, i) => i !== index));
    };

    const updateGrade = (index: number, field: string, value: string) => {
        const newGrades = [...data.grades];
        newGrades[index] = { ...newGrades[index], [field]: value };
        setData('grades', newGrades);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/grades');
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

        // If there are no grades, we'll show a message to add one
        if (data.grades.length === 0) {
            tour.addStep({
                id: 'no-grades-step',
                text: [
                    `<p>Let's add your first grade level.</p>`,
                    `<p>Click the 'Add Grade' button below to begin.</p>`
                ],
                attachTo: {
                    element: 'button[onClick="addGrade"]',
                    on: 'top'
                },
                buttons: [
                    {
                        text: 'Next',
                        action: tour.next
                    }
                ]
            });
        }

        // Step for the first grade level name
        tour.addStep({
            id: 'grade-name-step',
            text: [
                `<p>Enter the name of the grade level (e.g., Grade 1, Kindergarten).</p>`
            ],
            attachTo: {
                element: '#grade-name-0',
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

        // Step for the first grade level
        tour.addStep({
            id: 'grade-level-step',
            text: [
                `<p>Enter the numeric level for this grade (e.g., 1 for Grade 1).</p>`
            ],
            attachTo: {
                element: '#grade-level-0',
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

        // Step for the add grade button
        tour.addStep({
            id: 'add-grade-step',
            text: [
                `<p>Click 'Add Grade' to add more grade levels as needed.</p>`
            ],
            attachTo: {
                element: 'button[onClick="addGrade"]',
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
                `<p>You've added the grade levels for your school.</p>`,
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
    }, [data.grades]);

    return (
        <AppShell
            title="Grade Levels Setup"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Grade Levels' },
            ]}
        >
            <PageHeader
                title="Grade Levels Setup"
                description="Add grade levels to your school"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Grade Levels</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        {data.grades.map((grade, index) => (
                            <div key={index} className="grid gap-4 md:grid-cols-3 items-end">
                                <div>
                                    <Label htmlFor={`grade-name-${index}`}>Grade Name</Label>
                                    <Input
                                        id={`grade-name-${index}`}
                                        value={grade.name}
                                        onChange={(e) => updateGrade(index, 'name', e.target.value)}
                                        placeholder="e.g., Grade 1"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor={`grade-level-${index}`}>Level</Label>
                                    <Input
                                        id={`grade-level-${index}`}
                                        type="number"
                                        value={grade.level}
                                        onChange={(e) => updateGrade(index, 'level', e.target.value)}
                                        placeholder="1"
                                        required
                                    />
                                </div>
                                {data.grades.length > 1 && (
                                    <Button type="button" variant="destructive" onClick={() => removeGrade(index)}>
                                        Remove
                                    </Button>
                                )}
                            </div>
                        ))}

                        <Button type="button" variant="outline" onClick={addGrade}>
                            <Plus className="mr-2 h-4 w-4" />Add Grade
                        </Button>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding/academic-year"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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