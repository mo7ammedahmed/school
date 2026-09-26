import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';

type ScoreRow = {
    id: number;
    student_name: string;
    score: number | null;
    graded_by: string | null;
    graded_at: string | null;
};

type AssessmentScoresProps = {
    assessment: {
        id: number;
        name: string;
        max_score: number | null;
        subject: string | null;
        section: string | null;
    };
    scores: ScoreRow[];
};

export default function AssessmentScores({ assessment, scores }: AssessmentScoresProps) {
    const columns: ColumnDef<ScoreRow>[] = [
        {
            accessorKey: 'student_name',
            header: 'Student',
        },
        {
            accessorKey: 'score',
            header: 'Score',
            cell: ({ row }) =>
                row.original.score === null
                    ? 'Not graded'
                    : assessment.max_score === null
                      ? row.original.score.toFixed(2)
                      : `${row.original.score.toFixed(2)} / ${assessment.max_score.toFixed(2)}`,
        },
        {
            accessorKey: 'graded_by',
            header: 'Graded By',
            cell: ({ row }) => row.original.graded_by ?? '—',
        },
        {
            accessorKey: 'graded_at',
            header: 'Graded At',
            cell: ({ row }) => row.original.graded_at ?? '—',
        },
    ];

    return (
        <AppShell
            title="Assessment Scores"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assessments', href: '/assessments' },
                { label: assessment.name, href: `/assessments/${assessment.id}` },
                { label: 'Scores' },
            ]}
        >
            <PageHeader
                title="Scores"
                description={[assessment.name, assessment.subject, assessment.section].filter(Boolean).join(' · ')}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={`/assessments/${assessment.id}`}>
                            <ArrowLeft className="me-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={scores} emptyMessage="No scores recorded for this assessment yet." />
        </AppShell>
    );
}
