import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardianChildAssignments({ child, assignments }: { child: { first_name: string; last_name: string }; assignments: { title: string; due_date: string; status: string; offering: { subject: { name: string } } }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'title',
            header: 'Title',
        },
        {
            accessorKey: 'offering.subject.name',
            header: 'Subject',
        },
        {
            accessorKey: 'due_date',
            header: 'Due Date',
        },
        {
            accessorKey: 'status',
            header: 'Status',
        },
    ];

    return (
        <AppShell
            title="Child Assignments"
            breadcrumbs={[
                { label: 'Guardian Portal', href: '/guardian' },
                { label: 'Children', href: '/guardian/children' },
                { label: `${child.first_name}'s Assignments` },
            ]}
        >
            <PageHeader
                title={`${child.first_name} ${child.last_name}'s Assignments`}
                description="Assignment status"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/guardian/children"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={assignments} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
