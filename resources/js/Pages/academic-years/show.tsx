import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AcademicYearsShow({ academicYear }: { academicYear: { id: number; name: string; start_date: string; end_date: string; is_current: boolean } }) {
    return (
        <AppShell
            title="Academic Year Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Academic Years', href: '/academic-years' },
                { label: academicYear.name },
            ]}
        >
            <PageHeader
                title="Academic Year Details"
                description={academicYear.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/academic-years"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/academic-years/${academicYear.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Academic Year Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Academic Year</span>
                            <p className="text-base">{academicYear.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">{academicYear.is_current ? 'Current Year' : 'Past Year'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Start Date</span>
                            <p className="text-base">{academicYear.start_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">End Date</span>
                            <p className="text-base">{academicYear.end_date}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
