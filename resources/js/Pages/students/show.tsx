import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';


import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function StudentsShow({ student }: { student: { id: number; first_name: string; last_name: string; email: string; phone: string; date_of_birth: string; gender: string; address: string; student_id_number: string; guardian?: { first_name: string; last_name: string; email: string; phone: string } | null; enrollment_date: string; status: string } }) {
    return (
        <AppShell
            title="Student Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Students', href: '/students' },
                { label: `${student.first_name} ${student.last_name}` },
            ]}
        >
            <PageHeader
                title="Student Details"
                description={`${student.first_name} ${student.last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/students"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/students/${student.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Personal Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Full Name</span>
                            <p className="text-base">{student.first_name} {student.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Student ID</span>
                            <p className="text-base">{student.student_id_number}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Email</span>
                            <p className="text-base">{student.email}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Phone</span>
                            <p className="text-base">{student.phone || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Date of Birth</span>
                            <p className="text-base">{student.date_of_birth}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Gender</span>
                            <p className="text-base capitalize">{student.gender}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Address</span>
                            <p className="text-base">{student.address || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Guardian Information</CardTitle>
                </CardHeader>
                <CardContent>
                    {student.guardian ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <span className="text-sm font-medium text-muted-foreground">Guardian Name</span>
                                <p className="text-base">{student.guardian.first_name} {student.guardian.last_name}</p>
                            </div>
                            <div>
                                <span className="text-sm font-medium text-muted-foreground">Guardian Email</span>
                                <p className="text-base">{student.guardian.email}</p>
                            </div>
                            <div>
                                <span className="text-sm font-medium text-muted-foreground">Guardian Phone</span>
                                <p className="text-base">{student.guardian.phone || '-'}</p>
                            </div>
                        </div>
                    ) : (
                        <p className="text-base text-muted-foreground">No guardian information available.</p>
                    )}
                </CardContent>
            </Card>
        </AppShell>
    );
}
