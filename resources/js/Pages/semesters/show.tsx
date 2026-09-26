import { type ReactNode } from 'react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type Semester = {
    id: number;
    name: string;
    name_ar: string | null;
    name_en: string | null;
    code: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    academic_year?: { name: string } | null;
};

export default function SemestersShow({ semester }: { semester: Semester }) {
    return (
        <AppShell
            title="Semester Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Semesters', href: '/semesters' },
                { label: semester.code },
            ]}
        >
            <PageHeader
                title={semester.name}
                description={semester.code}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/semesters">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/semesters/${semester.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Semester Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Name (English)">{semester.name_en ?? '—'}</Field>
                        <Field label="Name (Arabic)">
                            <span dir="rtl">{semester.name_ar ?? '—'}</span>
                        </Field>
                        <Field label="Code">{semester.code}</Field>
                        <Field label="Academic Year">{semester.academic_year?.name ?? '—'}</Field>
                        <Field label="Start Date">{semester.start_date?.slice(0, 10)}</Field>
                        <Field label="End Date">{semester.end_date?.slice(0, 10)}</Field>
                        <Field label="Status">
                            {semester.is_current ? (
                                <Badge>Current</Badge>
                            ) : (
                                <Badge variant="secondary">Inactive</Badge>
                            )}
                        </Field>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            <p className="text-base">{children}</p>
        </div>
    );
}
