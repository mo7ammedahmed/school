import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function TeachersShow({ teacher }: { teacher: { id: number; first_name: string; last_name: string; email: string; phone: string; employee_id: string; specialization: string; qualification: string; date_of_birth: string; gender: string; address: string; hire_date: string; status: string } }) {
    return (
        <AppShell
            title="Teacher Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Teachers', href: '/teachers' },
                { label: `${teacher.first_name} ${teacher.last_name}` },
            ]}
        >
            <PageHeader
                title="Teacher Details"
                description={`${teacher.first_name} ${teacher.last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/teachers"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/teachers/${teacher.id}/edit`}>Edit</Link>
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
                            <p className="text-base">{teacher.first_name} {teacher.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Email</span>
                            <p className="text-base">{teacher.email}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Phone</span>
                            <p className="text-base">{teacher.phone || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Employee ID</span>
                            <p className="text-base">{teacher.employee_id}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Specialization</span>
                            <p className="text-base">{teacher.specialization || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Qualification</span>
                            <p className="text-base">{teacher.qualification || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Date of Birth</span>
                            <p className="text-base">{teacher.date_of_birth}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Gender</span>
                            <p className="text-base capitalize">{teacher.gender}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Address</span>
                            <p className="text-base">{teacher.address || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Hire Date</span>
                            <p className="text-base">{teacher.hire_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{teacher.status.replace('_', ' ')}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
