import { useMemo, useState, type FormEvent } from 'react';
import { router, useForm, Link } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { Download, ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { useLocale } from '@/lib/i18n/locale-context';
import { cn } from '@/lib/utils';

type CalendarItem = {
    date: string;
    end_date: string | null;
    type: string;
    title: string;
    source: string;
    id: number | null;
    href: string | null;
    time: string | null;
};

type Cell = {
    date: string;
    in_range: boolean;
    is_today: boolean;
    weekday: number;
    items: CalendarItem[];
};

type CalendarIndexProps = {
    view: 'month' | 'week' | 'day';
    anchor: string;
    range: { start: string; end: string };
    cells: Cell[];
    items: CalendarItem[];
    dayTypeOptions: Record<string, string>;
    filterOptions: string[];
};

const TYPE_STYLES: Record<string, string> = {
    holiday: 'bg-destructive/10 text-destructive',
    closure: 'bg-destructive/10 text-destructive',
    term_start: 'bg-primary/10 text-primary',
    term_end: 'bg-primary/10 text-primary',
    exam_period: 'bg-amber-500/10 text-amber-700',
    exam: 'bg-amber-500/10 text-amber-700',
    event: 'bg-accent text-accent-foreground',
    staff_workday: 'bg-muted text-muted-foreground',
    term: 'bg-primary/10 text-primary',
    assessment: 'bg-muted text-muted-foreground',
    assignment: 'bg-muted text-muted-foreground',
    admissions: 'bg-secondary text-secondary-foreground',
    announcement: 'bg-secondary text-secondary-foreground',
};

function typeLabel(type: string): string {
    return type.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function chunk<T>(items: T[], size: number): T[][] {
    const rows: T[][] = [];
    for (let index = 0; index < items.length; index += size) {
        rows.push(items.slice(index, index + size));
    }
    return rows;
}

export default function CalendarIndex({
    view,
    anchor,
    cells,
    dayTypeOptions,
    filterOptions,
}: CalendarIndexProps) {
    const { locale } = useLocale();
    const [activeTypes, setActiveTypes] = useState<string[]>(filterOptions);
    const [showForm, setShowForm] = useState(false);

    const form = useForm({
        title: '',
        type: 'holiday',
        date: anchor,
        end_date: '',
        description: '',
        is_instructional: false,
    });

    const dateFormatter = useMemo(
        () => new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short' }),
        [locale]
    );
    const weekdayFormatter = useMemo(
        () => new Intl.DateTimeFormat(locale, { weekday: 'short' }),
        [locale]
    );
    const headingFormatter = useMemo(
        () => new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }),
        [locale]
    );

    const anchorDate = new Date(anchor);
    const weekdays = useMemo(() => {
        // Cells always begin on the school week's first day.
        const start = new Date(cells[0]?.date ?? anchor);
        return Array.from({ length: 7 }, (_, offset) => {
            const day = new Date(start);
            day.setDate(start.getDate() + offset);
            return weekdayFormatter.format(day);
        });
    }, [cells, anchor, weekdayFormatter]);

    const visible = (items: CalendarItem[]): CalendarItem[] =>
        items.filter((item) => activeTypes.includes(item.type));

    const navigate = (date: Date, nextView: CalendarIndexProps['view']) => {
        router.get(
            '/calendar',
            { view: nextView, date: date.toISOString().slice(0, 10) },
            { preserveState: true, preserveScroll: true }
        );
    };

    const step = (direction: -1 | 1) => {
        const next = new Date(anchorDate);
        if (view === 'month') next.setMonth(next.getMonth() + direction);
        else if (view === 'week') next.setDate(next.getDate() + direction * 7);
        else next.setDate(next.getDate() + direction);
        navigate(next, view);
    };

    const toggleType = (type: string) => {
        setActiveTypes((prev) => (prev.includes(type) ? prev.filter((item) => item !== type) : [...prev, type]));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/calendar-days', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('title', 'end_date', 'description');
                setShowForm(false);
            },
        });
    };

    const weeks = view === 'month' ? chunk(cells, 7) : [cells];

    return (
        <AppShell
            title="Calendar"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Calendar' },
            ]}
        >
            <PageHeader
                title="Academic Calendar"
                description="Terms, exams, assessments, events, holidays and closures in one place"
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a href={`/calendar/export.ics?start=${anchor}&end=${anchor}`}>
                                <Download className="me-2 h-4 w-4" />
                                Export .ics
                            </a>
                        </Button>
                        <Button type="button" onClick={() => setShowForm((prev) => !prev)}>
                            <Plus className="me-2 h-4 w-4" />
                            Add entry
                        </Button>
                    </div>
                }
            />

            {showForm && (
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>New calendar entry</CardTitle>
                        <CardDescription>Holidays, closures, term boundaries and staff days.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4 md:grid-cols-3">
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    required
                                />
                                {form.errors.title && <p className="text-sm text-destructive">{form.errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="type">Type</Label>
                                <select
                                    id="type"
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={form.data.type}
                                    onChange={(e) => form.setData('type', e.target.value)}
                                >
                                    {Object.entries(dayTypeOptions).map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="date">Start date</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) => form.setData('date', e.target.value)}
                                    required
                                />
                                {form.errors.date && <p className="text-sm text-destructive">{form.errors.date}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="end_date">End date (optional)</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(e) => form.setData('end_date', e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-2 md:items-end">
                                <input
                                    id="is_instructional"
                                    type="checkbox"
                                    checked={form.data.is_instructional}
                                    onChange={(e) => form.setData('is_instructional', e.target.checked)}
                                />
                                <Label htmlFor="is_instructional">Teaching day</Label>
                            </div>

                            <div className="flex gap-3 md:col-span-3">
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Saving...' : 'Save entry'}
                                </Button>
                                <Button type="button" variant="outline" onClick={() => setShowForm(false)}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}

            <Card className="mt-6">
                <CardHeader className="gap-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => step(-1)} aria-label="Previous">
                                <ChevronLeft className="h-4 w-4 rtl:-scale-x-100" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => navigate(new Date(), view)}>
                                Today
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => step(1)} aria-label="Next">
                                <ChevronRight className="h-4 w-4 rtl:-scale-x-100" />
                            </Button>
                            <span className="ms-2 text-sm font-medium text-foreground">
                                {headingFormatter.format(anchorDate)}
                            </span>
                        </div>

                        <div className="flex gap-1 rounded-lg border border-border p-1">
                            {(['month', 'week', 'day'] as const).map((option) => (
                                <button
                                    key={option}
                                    type="button"
                                    onClick={() => navigate(anchorDate, option)}
                                    className={cn(
                                        'rounded-md px-3 py-1 text-sm capitalize transition-colors',
                                        view === option
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-muted-foreground hover:text-foreground'
                                    )}
                                >
                                    {option}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {filterOptions.map((type) => (
                            <button
                                key={type}
                                type="button"
                                onClick={() => toggleType(type)}
                                aria-pressed={activeTypes.includes(type)}
                                className={cn(
                                    'rounded-full border px-2.5 py-0.5 text-xs transition-opacity',
                                    TYPE_STYLES[type] ?? 'bg-muted text-muted-foreground',
                                    activeTypes.includes(type) ? 'border-transparent' : 'border-border opacity-40'
                                )}
                            >
                                {typeLabel(type)}
                            </button>
                        ))}
                    </div>
                </CardHeader>

                <CardContent>
                    {view === 'day' ? (
                        <DayView cell={cells[0]} items={cells[0] ? visible(cells[0].items) : []} />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[48rem] border-collapse">
                                <thead>
                                    <tr>
                                        {weekdays.map((weekday) => (
                                            <th
                                                key={weekday}
                                                scope="col"
                                                className="border-b border-border px-2 pb-2 text-start text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                            >
                                                {weekday}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {weeks.map((week, weekIndex) => (
                                        <tr key={weekIndex}>
                                            {week.map((cell) => (
                                                <td
                                                    key={cell.date}
                                                    className={cn(
                                                        'align-top border border-border/60 p-2',
                                                        !cell.in_range && 'bg-muted/40',
                                                        view === 'week' ? 'min-h-[10rem]' : 'h-28'
                                                    )}
                                                >
                                                    <div
                                                        className={cn(
                                                            'mb-1 flex items-center justify-between text-xs',
                                                            cell.is_today ? 'font-semibold text-primary' : 'text-muted-foreground'
                                                        )}
                                                    >
                                                        <span>{dateFormatter.format(new Date(cell.date))}</span>
                                                    </div>

                                                    <ul className="space-y-1">
                                                        {visible(cell.items).map((item, index) => (
                                                            <li key={`${item.source}-${item.id ?? index}-${item.date}`}>
                                                                <ItemChip item={item} />
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppShell>
    );
}

function ItemChip({ item }: { item: CalendarItem }) {
    const className = cn(
        'block truncate rounded px-1.5 py-0.5 text-xs',
        TYPE_STYLES[item.type] ?? 'bg-muted text-muted-foreground'
    );

    if (item.href) {
        return (
            <Link href={item.href} className={cn(className, 'hover:underline')} title={item.title}>
                {item.title}
            </Link>
        );
    }

    return (
        <span className={className} title={item.title}>
            {item.title}
        </span>
    );
}

function DayView({ cell, items }: { cell: Cell | undefined; items: CalendarItem[] }) {
    if (!cell || items.length === 0) {
        return <EmptyState title="Nothing scheduled" description="No calendar entries for this day." />;
    }

    return (
        <ul className="space-y-2">
            {items.map((item, index) => (
                <li
                    key={`${item.source}-${item.id ?? index}`}
                    className="flex items-center justify-between gap-4 rounded-lg border border-border px-3 py-2"
                >
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-foreground">{item.title}</p>
                        <p className="text-xs text-muted-foreground">{typeLabel(item.type)}</p>
                    </div>
                    {item.href && (
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={item.href}>Open</Link>
                        </Button>
                    )}
                </li>
            ))}
        </ul>
    );
}
