import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';

type Answers = Record<string, string | undefined>;

type Collected = {
    guardian?: Answers;
    student?: Answers;
    previous_school?: Answers;
    documents?: { files?: { name: string; type: string }[] };
};

const LABELS: Record<keyof Omit<Collected, 'documents'>, Record<string, string>> = {
    guardian: {
        first_name: 'First name',
        last_name: 'Last name',
        email: 'Email',
        phone: 'Phone',
        relationship: 'Relationship',
        occupation: 'Occupation',
        address: 'Address',
    },
    student: {
        first_name: 'First name',
        last_name: 'Last name',
        date_of_birth: 'Date of birth',
        gender: 'Gender',
        nationality: 'Nationality',
        address: 'Address',
        previous_school: 'Previous school',
    },
    previous_school: {
        school_name: 'School name',
        school_address: 'School address',
        last_grade_completed: 'Last grade completed',
        reason_for_leaving: 'Reason for leaving',
    },
};

function Section({ title, labels, answers }: { title: string; labels: Record<string, string>; answers?: Answers }) {
    const entries = Object.entries(labels).filter(([key]) => (answers?.[key] ?? '') !== '');

    return (
        <div>
            <h3 className="mb-2 font-semibold">{title}</h3>
            <div className="rounded-lg bg-gray-50 p-4">
                {entries.length === 0 ? (
                    <p className="text-gray-500">Nothing captured yet.</p>
                ) : (
                    entries.map(([key, label]) => (
                        <p key={key}>
                            <strong>{label}:</strong> {answers?.[key]}
                        </p>
                    ))
                )}
            </div>
        </div>
    );
}

export default function ApplyReview({ collected = {} }: { collected?: Collected }) {
    const { post, processing } = useForm({});

    // The wizard's answers live in the session until this call, which turns them
    // into an application. The button used to be a plain link, so nothing was
    // ever submitted.
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post('/apply/submit');
    };

    return (
        <AppShell
            title="Review Application"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Review' },
            ]}
        >
            <PageHeader
                title="Review Application"
                description="Please review your application details before submitting"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Application Summary</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-6">
                        <Section title="Guardian Information" labels={LABELS.guardian} answers={collected.guardian} />
                        <Section title="Student Information" labels={LABELS.student} answers={collected.student} />
                        <Section
                            title="Previous School"
                            labels={LABELS.previous_school}
                            answers={collected.previous_school}
                        />

                        <div>
                            <h3 className="mb-2 font-semibold">Uploaded Documents</h3>
                            <div className="rounded-lg bg-gray-50 p-4">
                                {collected.documents?.files?.length ? (
                                    <ul className="space-y-1">
                                        {collected.documents.files.map((file) => (
                                            <li key={file.type}>• {file.name}</li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-gray-500">No documents uploaded yet.</p>
                                )}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <form onSubmit={submit} className="mt-6 flex justify-between">
                <Button type="button" variant="outline" asChild>
                    <Link href="/apply/documents">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    Submit Application
                    <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
            </form>
        </AppShell>
    );
}
