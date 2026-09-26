import { Link } from '@inertiajs/react';
import { CalendarDays, LayoutGrid, List as ListIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type TimetableView = 'calendar' | 'grid' | 'list';

const VIEWS: { value: TimetableView; href: string; label: string; icon: typeof CalendarDays }[] = [
    // The calendar is the landing view, so it owns the bare /timetable path.
    { value: 'calendar', href: '/timetable', label: 'Calendar', icon: CalendarDays },
    { value: 'grid', href: '/timetable/grid', label: 'Weekly grid', icon: LayoutGrid },
    { value: 'list', href: '/timetable/list', label: 'List', icon: ListIcon },
];

/**
 * Switches between the three timetable views while carrying the current filters
 * along, so changing view never silently drops the section or teacher filter.
 */
export function TimetableViewSwitcher({
    current,
    query,
    className,
}: {
    current: TimetableView;
    /** Overrides the query string read from the address bar. */
    query?: string;
    className?: string;
}) {
    const search =
        query ?? (typeof window === 'undefined' ? '' : window.location.search.replace(/^\?/, ''));
    const suffix = search ? `?${search}` : '';

    return (
        <div
            role="tablist"
            aria-label="Timetable view"
            className={cn(
                'inline-flex items-center gap-1 rounded-lg border border-border/70 bg-card p-1',
                className,
            )}
        >
            {VIEWS.map((view) => {
                const active = view.value === current;
                const Icon = view.icon;

                return (
                    <Link
                        key={view.value}
                        href={`${view.href}${suffix}`}
                        role="tab"
                        aria-selected={active}
                        preserveScroll
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground',
                        )}
                    >
                        <Icon className="h-4 w-4" aria-hidden="true" />
                        {view.label}
                    </Link>
                );
            })}
        </div>
    );
}
