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

export default function OnboardingSubjects() {
    const { data, setData, post, processing } = useForm({
        subjects: [{ name: '', code: '', grade_level_id: '' }],
    });

    const addSubject = () => {
        setData('subjects', [...data.subjects, { name: '', code: '', grade_level_id: '' }]);
    };

    const removeSubject = (index: number) => {
        setData('subjects', data.subjects.filter((_, i) => i !== index));
    };

    const updateSubject = (index: number, field: string, value: string) => {
        const newSubjects = [...data.subjects];
        newSubjects[index] = { ...newSubjects[index], [field]: value };
        setData('subjects', newSubjects);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/subjects');
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

        // If there are no subjects, we'll show a message to add one
        if (data.subjects.length === 0) {
            tour.addStep({
                id: 'no-subjects-step',
                text: [
                    `<p>Let's add your first subject.</p>`,
                    `<p>Click the 'Add Subject' button below to begin.</p>`
                ],
                attachTo: {
                    element: 'button[onClick="addSubject"]',
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

        // Step for the first subject name
        tour.addStep({
            id: 'subject-name-step',
            text: [
                `<p>Enter the name of the subject (e.g., Mathematics, English).</p>`
            ],
            attachTo: {
                element: `#subject-name-0`,
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

        // Step for the first subject code
        tour.addStep({
            id: 'subject-code-step',
            text: [
                `<p>Enter the code for the subject (e.g., MATH101).</p>`
            ],
            attachTo: {
                element: `#subject-code-0`,
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

        // Step for the first subject grade level
        tour.addStep({
            id: 'subject-grade-level-step',
            text: [
                `<p>Select the grade level for this subject.</p>`
            ],
            attachTo: {
                element: `#subject-grade_level_id-0`,
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

        // Step for the add subject button
        tour.addStep({
            id: 'add-subject-step',
            text: [
                `<p>Click 'Add Subject' to add more subjects as needed.</p>`
            ],
            attachTo: {
                element: 'button[onClick="addSubject"]',
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
                `<p>You've added the subjects for your school's curriculum.</p>`,
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
    }, [data.subjects]);

    return (
        <AppShell
            title="Subjects Setup"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Subjects' },
            ]}
        >
            <PageHeader
                title="Subjects Setup"
                description="Add subjects to your curriculum"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Subjects</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        {data.subjects.map((subject, index) => (
                            <div key={index} className="grid gap-4 md:grid-cols-3 items-end">
                                <div>
                                    <Label htmlFor={`subject-name-${index}`}>Subject Name</Label>
                                    <Input
                                        id={`subject-name-${index}`}
                                        value={subject.name}
                                        onChange={(e) => updateSubject(index, 'name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor={`subject-code-${index}`}>Code</Label>
                                    <Input
                                        id={`subject-code-${index}`}
                                        value={subject.code}
                                        onChange={(e) => updateSubject(index, 'code', e.target.value)}
                                        required
                                    />
                                </div>
                                {data.subjects.length > 1 && (
                                    <Button type="button" variant="destructive" onClick={() => removeSubject(index)}>
                                        Remove
                                    </Button>
                                )}
                            </div>
                        ))}

                        <Button type="button" variant="outline" onClick={addSubject}>
                            Add Subject
                        </Button>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding/grades"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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