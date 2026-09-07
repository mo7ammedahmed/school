import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function EnrollmentsShow({ enrollment }: { enrollment: { id: number; student: { first_name: string; last_name: string; email: string }; section: { name: string }; academic_year: { name: string }; enrollment_date: string; status: string } }) {
    return (
        <AppShell
            title="Enrollment Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Enrollments', href: '/enrollments' },
                { label: `Enrollment #${enrollment.id}` },
            ]}
        >
            <PageHeader
                title="Enrollment Details"
                description={`${enrollment.student.first_name} ${enrollment.student.last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/enrollments"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Enrollment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student</span>
                            <p className="text-base">{enrollment.student.first_name} {enrollment.student.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student Email</span>
                            <p className="text-base">{enrollment.student.email}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{enrollment.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Academic Year</span>
                            <p className="text-base">{enrollment.academic_year.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Enrollment Date</span>
                            <p className="text-base">{enrollment.enrollment_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{enrollment.status}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
