import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Card, CardContent } from '@/components/ui/card';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

export default function GuardianChildren({ guardian, children }: { guardian: { name: string }; children: { id: number; first_name: string; last_name: string; date_of_birth: string; gender: string }[] }) {
    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'first_name',
            header: 'First Name',
            cell: ({ row }) => `${row.original.first_name} ${row.original.last_name}`,
        },
        {
            accessorKey: 'date_of_birth',
            header: 'Date of Birth',
        },
        {
            accessorKey: 'gender',
            header: 'Gender',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/guardian/children/${row.original.id}/schedule`}>Schedule</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/guardian/children/${row.original.id}/grades`}>Grades</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="My Children"
            breadcrumbs={[
                { label: 'Guardian Portal', href: '/guardian/dashboard' },
                { label: 'Children' },
            ]}
        >
            <PageHeader
                title="My Children"
                description={`${guardian.name}'s linked children`}
            />

            <Card>
                <CardContent>
                    <DataTable columns={columns} data={children} />
                </CardContent>
            </Card>
        </AppShell>
    );
}
