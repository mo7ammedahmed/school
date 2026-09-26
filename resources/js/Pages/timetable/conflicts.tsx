import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EmptyState } from '@/components/ui/empty-state';
import { CheckCircle2, ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type Conflict = {
    entry_id: number;
    conflicts_with: number;
    reasons: string[];
    label: string;
    other_label: string;
    time: string;
    other_time: string;
    day_of_week: string;
};

export default function TimetableConflicts({ conflicts }: { conflicts: Conflict[] }) {
    return (
        <AppShell
            title="Timetable Conflicts"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable', href: '/timetable' },
                { label: 'Conflicts' },
            ]}
        >
            <PageHeader
                title="Timetable Conflicts"
                description="Double-booked teachers, rooms and sections"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/timetable/grid">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            Back to grid
                        </Link>
                    </Button>
                }
            />

            <div className="mt-6">
                {conflicts.length === 0 ? (
                    <EmptyState
                        icon={<CheckCircle2 className="h-6 w-6" />}
                        title="No conflicts"
                        description="No teacher, room or section is double-booked in the current timetable."
                    />
                ) : (
                    <ul className="space-y-3">
                        {conflicts.map((conflict) => (
                            <li
                                key={`${conflict.entry_id}-${conflict.conflicts_with}`}
                                className="rounded-xl border border-border/80 bg-card p-4"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="destructive" className="capitalize">
                                        {conflict.day_of_week}
                                    </Badge>
                                    {conflict.reasons.map((reason) => (
                                        <Badge key={reason} variant="secondary" className="capitalize">
                                            {reason}
                                        </Badge>
                                    ))}
                                </div>

                                <div className="mt-3 grid gap-2 text-sm md:grid-cols-2">
                                    <div>
                                        <p className="font-medium text-foreground">{conflict.label}</p>
                                        <p className="text-muted-foreground">{conflict.time}</p>
                                        <Button variant="ghost" size="sm" asChild className="mt-1 px-0">
                                            <Link href={`/timetable/${conflict.entry_id}/edit`}>Edit entry</Link>
                                        </Button>
                                    </div>
                                    <div>
                                        <p className="font-medium text-foreground">{conflict.other_label}</p>
                                        <p className="text-muted-foreground">{conflict.other_time}</p>
                                        <Button variant="ghost" size="sm" asChild className="mt-1 px-0">
                                            <Link href={`/timetable/${conflict.conflicts_with}/edit`}>Edit entry</Link>
                                        </Button>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppShell>
    );
}
