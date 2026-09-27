import { router } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { EmptyState } from '@/components/ui/empty-state';
import { AlertTriangle, History, Download, Printer } from 'lucide-react';
import { TimetableViewSwitcher } from '@/components/timetable-view-switcher';

type GridEntry = {
    id: number;
    subject: string | null;
    section: string | null;
    teacher: string;
    room: string | null;
    start: string;
    end: string;
    is_published: boolean;
};

type Row = {
    id: number | null;
    label: string;
    start: string;
    end: string;
    is_break: boolean;
};

type Option = { id: number; name: string };

type TimetableGridProps = {
    /** URL this render belongs to, so filter changes stay on it. */
    basePath?: string;
    rows: Row[];
    days: string[];
    matrix: Record<string, Record<string, GridEntry[]>>;
    filters: { section_id: number | null; teacher_id: number | null; semester_id: number | null };
    sections: Option[];
    teachers: Option[];
    semesters: Option[];
    hasPeriods: boolean;
};

export default function TimetableGrid({
    basePath = '/timetable/grid',
    rows,
    days,
    matrix,
    filters,
    sections,
    teachers,
    semesters,
    hasPeriods,
}: TimetableGridProps) {
    const applyFilter = (key: string, value: string) => {
        const next = { ...filters, [key]: value === '' ? null : value };
        Object.keys(next).forEach((k) => {
            if (next[k as keyof typeof next] === null) delete next[k as keyof typeof next];
        });
        router.get(basePath, next, { preserveState: true, preserveScroll: true });
    };

    const query = new URLSearchParams();
    if (filters.section_id) query.set('section_id', String(filters.section_id));
    if (filters.teacher_id) query.set('teacher_id', String(filters.teacher_id));

    return (
        <AppShell
            title="Timetable Grid"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable', href: '/timetable' },
                { label: 'Grid' },
            ]}
        >
            <PageHeader
                title="Timetable Grid"
                description="Weekly schedule by section or teacher"
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <TimetableViewSwitcher current="grid" query={query.toString()} />
                        <Button variant="outline" asChild>
                            <a href={`/timetable/conflicts`}>
                                <AlertTriangle className="me-2 h-4 w-4" />
                                Conflicts
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            {/* The browser prints this one: it shapes Arabic and
                                shows the school's logo, which the dompdf export
                                cannot. */}
                            <a href={`/timetable/print?${query.toString()}`}>
                                <Printer className="me-2 h-4 w-4" />
                                Print / PDF
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={`/timetable/export/ics?${query.toString()}`}>
                                <Download className="me-2 h-4 w-4" />
                                .ics
                            </a>
                        </Button>
                    </div>
                }
            />

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-3">
                    <label className="space-y-2">
                        <span className="text-sm font-medium text-muted-foreground">Section</span>
                        <select
                            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={filters.section_id ?? ''}
                            onChange={(e) => applyFilter('section_id', e.target.value)}
                        >
                            <option value="">All sections</option>
                            {sections.map((section) => (
                                <option key={section.id} value={section.id}>
                                    {section.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="space-y-2">
                        <span className="text-sm font-medium text-muted-foreground">Teacher</span>
                        <select
                            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={filters.teacher_id ?? ''}
                            onChange={(e) => applyFilter('teacher_id', e.target.value)}
                        >
                            <option value="">All teachers</option>
                            {teachers.map((teacher) => (
                                <option key={teacher.id} value={teacher.id}>
                                    {teacher.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="space-y-2">
                        <span className="text-sm font-medium text-muted-foreground">Semester</span>
                        <select
                            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={filters.semester_id ?? ''}
                            onChange={(e) => applyFilter('semester_id', e.target.value)}
                        >
                            <option value="">All semesters</option>
                            {semesters.map((semester) => (
                                <option key={semester.id} value={semester.id}>
                                    {semester.name}
                                </option>
                            ))}
                        </select>
                    </label>
                </CardContent>
            </Card>

            <div className="mt-6">
                {rows.length === 0 ? (
                    <EmptyState
                        title="No timetable entries"
                        description="Create timetable entries, or add periods to build a bell schedule."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border/80 bg-card">
                        <table className="w-full min-w-[52rem] border-collapse">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-3 py-3 text-start text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                    >
                                        {hasPeriods ? 'Period' : 'Time'}
                                    </th>
                                    {days.map((day) => (
                                        <th
                                            key={day}
                                            scope="col"
                                            className="px-3 py-3 text-start text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                        >
                                            {day}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/70">
                                {rows.map((row, rowIndex) => (
                                    <tr key={`${row.start}-${rowIndex}`}>
                                        <th
                                            scope="row"
                                            className="w-40 px-3 py-3 text-start align-top text-sm font-medium text-foreground"
                                        >
                                            <span className="block">{row.label}</span>
                                            <span className="block text-xs font-normal text-muted-foreground">
                                                {row.start} - {row.end}
                                            </span>
                                            {row.is_break && (
                                                <Badge variant="secondary" className="mt-1">
                                                    Break
                                                </Badge>
                                            )}
                                        </th>

                                        {days.map((day) => {
                                            const entries = matrix[day]?.[rowIndex] ?? [];

                                            return (
                                                <td key={`${day}-${rowIndex}`} className="px-3 py-3 align-top">
                                                    {entries.length === 0 ? (
                                                        <span className="text-xs text-muted-foreground">—</span>
                                                    ) : (
                                                        <ul className="space-y-1.5">
                                                            {entries.map((entry) => (
                                                                <li
                                                                    key={entry.id}
                                                                    className="rounded-lg border border-border/70 bg-background px-2 py-1.5"
                                                                >
                                                                    <p className="text-sm font-medium text-foreground">
                                                                        {entry.subject ?? '—'}
                                                                    </p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        {[entry.section, entry.teacher, entry.room]
                                                                            .filter(Boolean)
                                                                            .join(' · ')}
                                                                    </p>
                                                                    {!entry.is_published && (
                                                                        <Badge variant="secondary" className="mt-1">
                                                                            Draft
                                                                        </Badge>
                                                                    )}
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {!hasPeriods && rows.length > 0 && (
                <p className="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                    <History className="h-4 w-4" />
                    Rows are derived from existing entry times. Add periods to get a consistent bell schedule.
                </p>
            )}
        </AppShell>
    );
}
