import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

export default function GuardianDashboard({ guardian, children }: { guardian: { name: string }; children: { id: number; name: string; attendance_rate: number; average_grade: number; outstanding_fees: number }[] }) {
    return (
        <AppShell
            title="Guardian Portal"
            breadcrumbs={[
                { label: 'Guardian Portal' },
            ]}
        >
            <PageHeader
                title={`Welcome, ${guardian.name}`}
                description="Monitor your children's academic progress"
            />

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {children.map((child) => (
                    <Card key={child.id} className="hover:shadow-md transition-shadow">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                {child.name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Attendance</span>
                                    <span className="text-[1.5rem] font-semibold leading-[1.2] tracking-[-0.005em] tabular-nums">{Number(child.attendance_rate).toFixed(1)}%</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Average Grade</span>
                                    <span className="text-[1.5rem] font-semibold leading-[1.2] tracking-[-0.005em] tabular-nums">{Number(child.average_grade).toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Outstanding Fees</span>
                                    <span className="text-[1.5rem] font-semibold leading-[1.2] tracking-[-0.005em] tabular-nums">${Number(child.outstanding_fees).toFixed(2)}</span>
                                </div>
                            </div>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/guardian/children/${child.id}/schedule`}>Schedule</Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/guardian/children/${child.id}/attendance`}>Attendance</Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/guardian/children/${child.id}/grades`}>Grades</Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/guardian/children/${child.id}/fees`}>Fees</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppShell>
    );
}
