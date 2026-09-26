import { router } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { Building2, Pencil, Plus, Trash2, Check } from 'lucide-react';

type SchoolRow = {
    id: number;
    name: string;
    name_ar: string | null;
    name_en: string | null;
    slug: string;
    city: string | null;
    country: string;
    locale: string;
    currency: string;
    primary_color: string | null;
    organization: { id: number; name: string } | null;
    students_count: number;
    teachers_count: number;
    is_active: boolean;
};

export default function SchoolsIndex({ schools }: { schools: SchoolRow[] }) {
    const remove = (school: SchoolRow) => {
        if (!window.confirm(`Delete ${school.name}? This cannot be undone.`)) return;
        router.delete(`/schools/${school.id}`, { preserveScroll: true });
    };

    return (
        <AppShell
            title="Schools & branches"
            breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Schools & branches' }]}
        >
            <PageHeader
                title="Schools & branches"
                description="Every school and branch in your organization, with its own branding, locale and currency."
                actions={
                    <Button asChild>
                        <a href="/schools/create">
                            <Plus className="me-2 h-4 w-4" />
                            New school
                        </a>
                    </Button>
                }
            />

            <div className="mt-6">
                {schools.length === 0 ? (
                    <EmptyState
                        title="No schools yet"
                        description="Create your first school or branch to get started."
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {schools.map((school) => (
                            <Card key={school.id} className="h-fit">
                                <CardContent className="space-y-4 pt-6">
                                    <div className="flex items-start gap-3">
                                        <span
                                            className="flex size-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                            style={{ backgroundColor: school.primary_color ?? 'var(--color-primary)' }}
                                        >
                                            <Building2 className="h-5 w-5" aria-hidden="true" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <p className="truncate text-sm font-semibold text-foreground">
                                                    {school.name}
                                                </p>
                                                {school.is_active && (
                                                    <Badge variant="secondary" className="gap-1">
                                                        <Check className="h-3 w-3" />
                                                        Active
                                                    </Badge>
                                                )}
                                            </div>
                                            {school.name_ar && (
                                                <p className="truncate text-xs text-muted-foreground" dir="rtl">
                                                    {school.name_ar}
                                                </p>
                                            )}
                                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                {school.organization?.name ?? 'No organization'} ·{' '}
                                                {[school.city, school.country].filter(Boolean).join(', ')}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-3 gap-2 rounded-lg border border-border/70 bg-muted/40 p-3 text-center">
                                        <div>
                                            <p className="text-lg font-semibold text-foreground">{school.students_count}</p>
                                            <p className="text-xs text-muted-foreground">Students</p>
                                        </div>
                                        <div>
                                            <p className="text-lg font-semibold text-foreground">{school.teachers_count}</p>
                                            <p className="text-xs text-muted-foreground">Teachers</p>
                                        </div>
                                        <div>
                                            <p className="text-lg font-semibold uppercase text-foreground">{school.locale}</p>
                                            <p className="text-xs text-muted-foreground">{school.currency}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Button variant="outline" size="sm" asChild className="flex-1">
                                            <a href={`/schools/${school.id}/edit`}>
                                                <Pencil className="me-2 h-3.5 w-3.5" />
                                                Edit
                                            </a>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => remove(school)}
                                            aria-label={`Delete ${school.name}`}
                                            title={`Delete ${school.name}`}
                                        >
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppShell>
    );
}
