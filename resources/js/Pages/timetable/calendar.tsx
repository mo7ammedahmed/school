import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, AlertTriangle, Printer, CalendarDays } from 'lucide-react';
import { TimetableViewSwitcher } from '@/components/timetable-view-switcher';
import { cn } from '@/lib/utils';

type Lesson = {
    id: number;
    subject: string | null;
    section: string | null;
    teacher: string;
    room: string | null;
    start: string;
    end: string;
    is_published: boolean;
};

type Cell = {
    date: string;
    weekday: string;
    day_label: number;
    in_month: boolean;
    is_today: boolean;
    is_school_day: boolean;
    closure: string | null;
    lessons: Lesson[];
};

type Option = { id: number; name: string };

type TimetableCalendarProps = {
    /** URL this render belongs to, so month/filter changes stay on it. */
    basePath?: string;
    month: string;
    month_label: string;
    days: string[];
    cells: Cell[];
    lesson_count: number;
    filters: { section_id: number | null; teacher_id: number | null; semester_id: number | null };
    sections: Option[];
    teachers: Option[];
    semesters: Option[];
};

const DAY_LABELS: Record<string, string> = {
    sunday: 'Sun',
    monday: 'Mon',
    tuesday: 'Tue',
    wednesday: 'Wed',
    thursday: 'Thu',
    friday: 'Fri',
    saturday: 'Sat',
};

function chunk<T>(items: T[], size: number): T[][] {
    const rows: T[][] = [];
    for (let index = 0; index < items.length; index += size) {
        rows.push(items.slice(index, index + size));
    }
    return rows;
}

/** Maximum slot chips drawn per day before we collapse into "+N more". */
const MAX_SLOTS_PER_DAY = 4;

type Slot = { key: string; start: string; end: string; lessons: Lesson[] };

/**
 * Collapse a day's lessons into one chip per time slot. Without a section or
 * teacher filter every section teaches at once, so the raw lesson list would
 * run to thousands of pixels; one chip per slot keeps the month readable and
 * shows the count when several classes share a slot.
 */
function slotsForDay(lessons: Lesson[]): Slot[] {
    const slots = new Map<string, Slot>();

    for (const lesson of lessons) {
        const key = `${lesson.start}-${lesson.end}`;
        const slot = slots.get(key);

        if (slot) {
            slot.lessons.push(lesson);
            continue;
        }

        slots.set(key, { key, start: lesson.start, end: lesson.end, lessons: [lesson] });
    }

    return [...slots.values()].sort((a, b) => a.start.localeCompare(b.start));
}

/** Distinct, human-readable subject names for a collapsed slot. */
function slotSubjects(slot: Slot): string {
    const names = [...new Set(slot.lessons.map((lesson) => lesson.subject).filter(Boolean))];
    return names.length > 0 ? names.join(' · ') : '—';
}

export default function TimetableCalendar({
    basePath = '/timetable/calendar',
    month,
    month_label,
    cells,
    lesson_count,
    filters,
    sections,
    teachers,
    semesters,
}: TimetableCalendarProps) {
    const weeks = useMemo(() => chunk(cells, 7), [cells]);

    const query = useMemo(() => {
        const params = new URLSearchParams();
        if (filters.section_id) params.set('section_id', String(filters.section_id));
        if (filters.teacher_id) params.set('teacher_id', String(filters.teacher_id));
        if (filters.semester_id) params.set('semester_id', String(filters.semester_id));
        return params;
    }, [filters]);

    const navigate = (nextMonth: string) => {
        const params = new URLSearchParams(query);
        params.set('date', `${nextMonth}-01`);
        router.get(basePath, Object.fromEntries(params), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const applyFilter = (key: string, value: string) => {
        const params = new URLSearchParams(query);
        if (value === '') params.delete(key);
        else params.set(key, value);
        params.set('date', `${month}-01`);
        router.get(basePath, Object.fromEntries(params), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const step = (direction: -1 | 1) => {
        const [year, monthNumber] = month.split('-').map(Number);
        const next = new Date(year, monthNumber - 1 + direction, 1);
        navigate(`${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`);
    };

    const todayMonth = new Date().toISOString().slice(0, 7);

    return (
        <AppShell
            title="Timetable Calendar"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable', href: '/timetable' },
                { label: 'Calendar' },
            ]}
        >
            <PageHeader
                title="Timetable Calendar"
                description="The weekly timetable spread across the month, with holidays and closures respected"
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <TimetableViewSwitcher current="calendar" query={query.toString()} />
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

            <Card className="mt-6">
                <CardHeader className="gap-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => step(-1)} aria-label="Previous month">
                                <ChevronLeft className="h-4 w-4 rtl:-scale-x-100" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => navigate(todayMonth)}>
                                Today
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => step(1)} aria-label="Next month">
                                <ChevronRight className="h-4 w-4 rtl:-scale-x-100" />
                            </Button>
                            <span className="ms-2 text-sm font-medium text-foreground">{month_label}</span>
                        </div>

                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                            <CalendarDays className="h-4 w-4" />
                            {lesson_count} lesson{lesson_count === 1 ? '' : 's'} this month
                        </span>
                    </div>
                </CardHeader>

                <CardContent>
                    <div className="overflow-x-auto">
                        {/* Fixed 7-column layout so the month always reads as one
                            calendar page instead of a horizontally scrolling grid. */}
                        <table className="w-full min-w-[34rem] table-fixed border-collapse">
                            <thead>
                                <tr>
                                    {['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].map(
                                        (weekday) => (
                                            <th
                                                key={weekday}
                                                scope="col"
                                                className="border-b border-border px-2 pb-2 text-start text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                            >
                                                {DAY_LABELS[weekday]}
                                            </th>
                                        )
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {weeks.map((week, weekIndex) => (
                                    <tr key={weekIndex}>
                                        {week.map((cell) => {
                                            const slots = slotsForDay(cell.lessons);
                                            const visible = slots.slice(0, MAX_SLOTS_PER_DAY);
                                            const hidden = slots.length - visible.length;

                                            return (
                                            <td
                                                key={cell.date}
                                                className={cn(
                                                    'h-40 align-top border border-border/60 p-1.5',
                                                    !cell.in_month && 'bg-muted/40',
                                                    !cell.is_school_day && cell.in_month && 'bg-muted/30'
                                                )}
                                            >
                                                <div className="mb-1.5 flex items-center justify-between gap-2">
                                                    <span
                                                        className={cn(
                                                            'flex size-6 items-center justify-center rounded-full text-xs',
                                                            cell.is_today
                                                                ? 'bg-primary font-semibold text-primary-foreground'
                                                                : 'text-muted-foreground'
                                                        )}
                                                    >
                                                        {cell.day_label}
                                                    </span>
                                                    {cell.closure && (
                                                        <Badge variant="secondary" className="truncate" title={cell.closure}>
                                                            {cell.closure}
                                                        </Badge>
                                                    )}
                                                </div>

                                                <ul className="space-y-1">
                                                    {visible.map((slot) => {
                                                        const single = slot.lessons.length === 1;
                                                        const lesson = slot.lessons[0];

                                                        return (
                                                            <li
                                                                key={slot.key}
                                                                className="rounded-md border border-border/70 bg-card px-1.5 py-1"
                                                                title={`${slot.start}–${slot.end} · ${slotSubjects(slot)}`}
                                                            >
                                                                <p className="font-mono text-[0.7rem] text-muted-foreground">
                                                                    {slot.start}
                                                                </p>
                                                                <p className="truncate text-xs font-medium text-foreground">
                                                                    {single
                                                                        ? lesson.subject ?? '—'
                                                                        : `${slot.lessons.length} classes`}
                                                                </p>
                                                                {single && (
                                                                    <p className="truncate text-[0.7rem] text-muted-foreground">
                                                                        {[lesson.room, lesson.is_published ? null : 'Draft']
                                                                            .filter(Boolean)
                                                                            .join(' · ') || lesson.teacher}
                                                                    </p>
                                                                )}
                                                            </li>
                                                        );
                                                    })}
                                                </ul>

                                                {hidden > 0 && (
                                                    <p
                                                        dir="ltr"
                                                        className="mt-1 truncate text-end text-[0.7rem] text-muted-foreground rtl:text-start"
                                                    >
                                                        +{hidden} more
                                                    </p>
                                                )}
                                            </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <span className="size-3 rounded-sm bg-muted/60 ring-1 ring-border" />
                            Weekend / non-teaching day
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Badge variant="secondary">Holiday</Badge>
                            Lessons are suppressed on closures.
                        </span>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
