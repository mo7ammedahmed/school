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

export default function OnboardingTeachers() {
    const { data, setData, post, processing } = useForm({
        teachers: [{ first_name: '', last_name: '', email: '', employee_id: '' }],
    });

    const addTeacher = () => {
        setData('teachers', [...data.teachers, { first_name: '', last_name: '', email: '', employee_id: '' }]);
    };

    const removeTeacher = (index: number) => {
        setData('teachers', data.teachers.filter((_, i) => i !== index));
    };

    const updateTeacher = (index: number, field: string, value: string) => {
        const newTeachers = [...data.teachers];
        newTeachers[index] = { ...newTeachers[index], [field]: value };
        setData('teachers', newTeachers);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/onboarding/teachers');
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

        // If there are no teachers, we'll show a message to add one
        if (data.teachers.length === 0) {
            tour.addStep({
                id: 'no-teachers-step',
                text: [
                    `<p>Let's add your first teacher.</p>`,
                    `<p>Click the 'Add Teacher' button below to begin.</p>`
                ],
                attachTo: {
                    element: 'button[onClick="addTeacher"]',
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

        // Step for the first teacher first name
        tour.addStep({
            id: 'teacher-first-name-step',
            text: [
                `<p>Enter the first name of the teacher.</p>`
            ],
            attachTo: {
                element: `#teacher-first-0`,
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

        // Step for the first teacher last name
        tour.addStep({
            id: 'teacher-last-name-step',
            text: [
                `<p>Enter the last name of the teacher.</p>`
            ],
            attachTo: {
                element: `#teacher-last-0`,
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

        // Step for the first teacher email
        tour.addStep({
            id: 'teacher-email-step',
            text: [
                `<p>Enter the email address of the teacher.</p>`
            ],
            attachTo: {
                element: `#teacher-email-0`,
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

        // Step for the first teacher employee ID
        tour.addStep({
            id: 'teacher-employee-id-step',
            text: [
                `<p>Enter the employee ID for the teacher (optional).</p>`
            ],
            attachTo: {
                element: `#teacher-employee_id-0`,
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

        // Step for the add teacher button
        tour.addStep({
            id: 'add-teacher-step',
            text: [
                `<p>Click 'Add Teacher' to add more teachers as needed.</p>`
            ],
            attachTo: {
                element: 'button[onClick="addTeacher"]',
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
                `<p>You've added the teachers for your school.</p>`,
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
    }, [data.teachers]);

    return (
        <AppShell
            title="Teachers Setup"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Teachers' },
            ]}
        >
            <PageHeader
                title="Teachers Setup"
                description="Add teachers to your school"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Teachers</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        {data.teachers.map((teacher, index) => (
                            <div key={index} className="grid gap-4 md:grid-cols-4 items-end">
                                <div>
                                    <Label htmlFor={`teacher-first-${index}`}>First Name</Label>
                                    <Input
                                        id={`teacher-first-${index}`}
                                        value={teacher.first_name}
                                        onChange={(e) => updateTeacher(index, 'first_name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor={`teacher-last-${index}`}>Last Name</Label>
                                    <Input
                                        id={`teacher-last-${index}`}
                                        value={teacher.last_name}
                                        onChange={(e) => updateTeacher(index, 'last_name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor={`teacher-email-${index}`}>Email</Label>
                                    <Input
                                        id={`teacher-email-${index}`}
                                        type="email"
                                        value={teacher.email}
                                        onChange={(e) => updateTeacher(index, 'email', e.target.value)}
                                        required
                                    />
                                </div>
                                {data.teachers.length > 1 && (
                                    <Button type="button" variant="destructive" onClick={() => removeTeacher(index)}>
                                        Remove
                                    </Button>
                                )}
                            </div>
                        ))}

                        <Button type="button" variant="outline" onClick={addTeacher}>
                            <Plus className="mr-2 h-4 w-4" />Add Teacher
                        </Button>

                        <div className="flex justify-between">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/onboarding/subjects"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
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