import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CalendarCheck, TrendingUp, ClipboardList, DollarSign } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function StudentDashboard({ student, stats }: { student: { first_name: string; last_name: string }; stats: { attendance_rate: number; average_grade: number; pending_assignments: number; outstanding_fees: number } }) {
    const statCards = [
        {
            title: 'Attendance Rate',
            value: `${Number(stats.attendance_rate).toFixed(1)}%`,
            icon: CalendarCheck,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
            link: '/student/attendance',
        },
        {
            title: 'Average Grade',
            value: Number(stats.average_grade).toFixed(2),
            icon: TrendingUp,
            color: 'text-green-600',
            bgColor: 'bg-green-50',
            link: '/student/grades',
        },
        {
            title: 'Pending Assignments',
            value: stats.pending_assignments,
            icon: ClipboardList,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
            link: '/student/assignments',
        },
        {
            title: 'Outstanding Fees',
            value: `$${Number(stats.outstanding_fees).toFixed(2)}`,
            icon: DollarSign,
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
            link: '/student/fees',
        },
    ];

    return (
        <AppShell
            title="Student Portal"
            breadcrumbs={[
                { label: 'Student Portal' },
            ]}
        >
            <PageHeader
                title={`Welcome, ${student.first_name}`}
                description="Here's your academic overview"
            />

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {statCards.map((stat) => (
                    <Link key={stat.title} href={stat.link}>
                        <Card className="hover:shadow-md transition-shadow cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {stat.title}
                                </CardTitle>
                                <stat.icon className={`h-4 w-4 ${stat.color}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stat.value}</div>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </AppShell>
    );
}
