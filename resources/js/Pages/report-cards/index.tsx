import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Paginator } from '@/components/ui/data-table';
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';
import { useBilingual } from '@/lib/i18n/bilingual';

type ReportCard = {
    id: number;
    student: { first_name: string | null; last_name: string | null };
    academic_year: { name_en: string | null; name_ar: string | null };
    gpa: number;
    comments: string | null;
    comments_ar: string | null;
    published_at: string | null;
};

export default function ReportCardsIndex({ reportCards }: { reportCards: Paginator<ReportCard> }) {
    const bilingual = useBilingual();

    const columns: ColumnDef<ReportCard>[] = [
        {
            id: 'student',
            header: 'Student',
            cell: ({ row }) => `${row.original.student.first_name ?? ''} ${row.original.student.last_name ?? ''}`,
        },
        {
            id: 'year',
            header: 'Academic Year',
            cell: ({ row }) => bilingual(row.original.academic_year.name_en, row.original.academic_year.name_ar),
        },
        {
            accessorKey: 'gpa',
            header: 'GPA',
            cell: ({ row }) => Number(row.original.gpa).toFixed(2),
        },
        {
            id: 'comments',
            header: 'Comments',
            cell: ({ row }) => bilingual(row.original.comments, row.original.comments_ar),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) =>
                row.original.published_at ? <Badge>Published</Badge> : <Badge variant="secondary">Draft</Badge>,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/report-cards/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/report-cards/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Report Cards"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards' },
            ]}
        >
            <PageHeader
                title="Report Cards"
                description="Manage student report cards"
                actions={
                    <Button asChild>
                        <Link href="/report-cards/create"><Plus className="me-2 h-4 w-4" />New Report Card</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={reportCards} />
        </AppShell>
    );
}
