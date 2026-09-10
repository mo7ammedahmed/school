import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardianChildGrades({ child, reportCards }: { child: { first_name: string; last_name: string }; reportCards: { id: number; academic_year?: { name: string }; gpa?: number; comments?: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            id: 'year',
            accessorFn: (row) => row.academic_year?.name ?? '—',
            header: 'Academic Year',
        },
        {
            accessorKey: 'gpa',
            header: 'GPA',
            cell: ({ row }) => Number(row.original.gpa).toFixed(2),
        },
        {
            accessorKey: 'comments',
            header: 'Comments',
            cell: ({ row }) => row.original.comments ?? '—',
        },
    ];

    return (
        <AppShell
            title="Child Grades"
            breadcrumbs={[
                { label: 'Guardian Portal', href: '/guardian/dashboard' },
                { label: 'Children', href: '/guardian/children' },
                { label: `${child.first_name}'s Grades` },
            ]}
        >
            <PageHeader
                title={`${child.first_name} ${child.last_name}'s Grades`}
                description="Report cards and grades"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/guardian/children"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={reportCards} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
