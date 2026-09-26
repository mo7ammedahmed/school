import { type ReactNode } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type Assessment = {
    id: number;
    name: string;
    description: string | null;
    category: string | null;
    subject: string | null;
    section: string | null;
    due_date: string | null;
    max_score: number | null;
    weight: number | null;
    is_published: boolean;
};

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            <p className="text-base">{children}</p>
        </div>
    );
}

export default function AssessmentsShow({ assessment }: { assessment: Assessment }) {
    return (
        <AppShell
            title="Assessment Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assessments', href: '/assessments' },
                { label: assessment.name },
            ]}
        >
            <PageHeader
                title="Assessment Details"
                description={assessment.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/assessments">
                                <ArrowLeft className="me-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/assessments/${assessment.id}/scores`}>Scores</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/assessments/${assessment.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Assessment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Assessment Name">{assessment.name}</Field>
                        <Field label="Grading Category">{assessment.category ?? '—'}</Field>
                        <Field label="Subject">{assessment.subject ?? '—'}</Field>
                        <Field label="Section">{assessment.section ?? '—'}</Field>
                        <Field label="Due Date">{assessment.due_date ?? '—'}</Field>
                        <Field label="Max Score">
                            {assessment.max_score === null ? '—' : assessment.max_score.toFixed(2)}
                        </Field>
                        <Field label="Weight">
                            {assessment.weight === null ? '—' : `${assessment.weight}%`}
                        </Field>
                        <Field label="Status">
                            {assessment.is_published ? <Badge>Published</Badge> : <Badge variant="secondary">Draft</Badge>}
                        </Field>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="whitespace-pre-wrap text-base">{assessment.description || '—'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
