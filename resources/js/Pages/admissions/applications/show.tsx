import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, X, Repeat } from 'lucide-react';

interface ApplicationEvent {
    id: number;
    event_type: string;
    notes?: string;
    created_at: string;
}

interface Application {
    id: number;
    reference: string;
    status: string;
    guardian_first_name: string;
    guardian_last_name: string;
    guardian_email: string;
    guardian_phone?: string;
    guardian_relationship?: string;
    student_first_name: string;
    student_last_name: string;
    student_date_of_birth?: string;
    student_gender?: string;
    student_nationality?: string;
    grade_applying?: string;
    previous_school_name?: string;
    previous_school_last_grade?: string;
    previous_school_year_completed?: string;
    documents?: { name: string; path: string; type?: string }[];
    review_notes?: string;
    submitted_at?: string;
    events?: ApplicationEvent[];
}

export default function AdmissionsApplicationsShow({ application }: { application: Application }) {
    const decideForm = useForm<{ decision: 'approved' | 'rejected'; notes: string }>({
        decision: 'approved',
        notes: '',
    });

    const convertForm = useForm({});

    const statusColors: Record<string, string> = {
        draft: 'bg-muted',
        submitted: 'bg-info',
        under_review: 'bg-warning',
        approved: 'bg-success',
        rejected: 'bg-destructive',
        converted: 'bg-success',
        withdrawn: 'bg-muted',
    };

    const canDecide = application.status === 'submitted' || application.status === 'under_review';
    const canConvert = application.status === 'approved' && !application.events?.some((e) => e.event_type === 'converted');

    return (
        <AppShell
            title={`Application ${application.reference}`}
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Admissions', href: '/admissions/applications' },
                { label: application.reference },
            ]}
        >
            <PageHeader
                title={application.reference}
                description={`Application for ${application.student_first_name} ${application.student_last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/admissions/applications">
                                <ArrowLeft className="mr-2 h-4 w-4" />Back
                            </Link>
                        </Button>
                        {canDecide && (
                            <>
                                <Button
                                    variant="destructive"
                                    disabled={decideForm.processing}
                                    onClick={() => {
                                        decideForm.setData('decision', 'rejected');
                                        decideForm.post(`/admissions/applications/${application.id}/decide`);
                                    }}
                                >
                                    <X className="mr-2 h-4 w-4" />Reject
                                </Button>
                                <Button
                                    disabled={decideForm.processing}
                                    onClick={() => {
                                        decideForm.setData('decision', 'approved');
                                        decideForm.post(`/admissions/applications/${application.id}/decide`);
                                    }}
                                >
                                    <Check className="mr-2 h-4 w-4" />Approve
                                </Button>
                            </>
                        )}
                        {canConvert && (
                            <Button
                                disabled={convertForm.processing}
                                onClick={() => convertForm.post(`/admissions/applications/${application.id}/convert`)}
                            >
                                <Repeat className="mr-2 h-4 w-4" />Convert to Student
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <span
                            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                statusColors[application.status] || 'bg-muted'
                            } text-white`}
                        >
                            {application.status}
                        </span>
                        {application.submitted_at && (
                            <p className="text-sm text-muted-foreground">
                                Submitted {new Date(application.submitted_at).toLocaleDateString()}
                            </p>
                        )}
                        {application.review_notes && (
                            <div>
                                <Label>Review Notes</Label>
                                <p className="text-sm">{application.review_notes}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Guardian Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Name</Label>
                            <p className="font-medium">
                                {application.guardian_first_name} {application.guardian_last_name}
                            </p>
                        </div>
                        <div>
                            <Label>Email</Label>
                            <p className="font-medium">{application.guardian_email}</p>
                        </div>
                        <div>
                            <Label>Phone</Label>
                            <p className="font-medium">{application.guardian_phone ?? '—'}</p>
                        </div>
                        <div>
                            <Label>Relationship</Label>
                            <p className="font-medium">{application.guardian_relationship ?? '—'}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Student Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Name</Label>
                            <p className="font-medium">
                                {application.student_first_name} {application.student_last_name}
                            </p>
                        </div>
                        <div>
                            <Label>Date of Birth</Label>
                            <p className="font-medium">{application.student_date_of_birth ?? '—'}</p>
                        </div>
                        <div>
                            <Label>Gender</Label>
                            <p className="font-medium">{application.student_gender ?? '—'}</p>
                        </div>
                        <div>
                            <Label>Nationality</Label>
                            <p className="font-medium">{application.student_nationality ?? '—'}</p>
                        </div>
                        <div>
                            <Label>Grade Applying For</Label>
                            <p className="font-medium">{application.grade_applying ?? '—'}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Previous School</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>School Name</Label>
                            <p className="font-medium">{application.previous_school_name ?? '—'}</p>
                        </div>
                        <div>
                            <Label>Last Grade Completed</Label>
                            <p className="font-medium">{application.previous_school_last_grade ?? '—'}</p>
                        </div>
                        <div>
                            <Label>Year Completed</Label>
                            <p className="font-medium">{application.previous_school_year_completed ?? '—'}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Documents</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(application.documents ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">No documents uploaded.</p>
                        ) : (
                            <div className="space-y-2">
                                {application.documents!.map((doc, index) => (
                                    <div key={index} className="flex items-center justify-between rounded-md border p-3">
                                        <span className="font-medium">{doc.name}</span>
                                        <Badge variant="secondary">uploaded</Badge>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Status History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(application.events ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">No history yet.</p>
                        ) : (
                            <ol className="space-y-3">
                                {application.events!.map((event) => (
                                    <li key={event.id} className="flex items-start gap-3 border-b pb-3 last:border-0">
                                        <Badge variant="outline">{event.event_type}</Badge>
                                        <div className="text-sm">
                                            {event.notes && <p>{event.notes}</p>}
                                            <p className="text-muted-foreground">
                                                {new Date(event.created_at).toLocaleString()}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
