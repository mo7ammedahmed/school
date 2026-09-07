import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { EmptyState } from '@/components/ui/empty-state';
import { Search } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Application {
    id: number;
    reference: string;
    student_first_name: string;
    student_last_name: string;
    grade_applying?: string;
    status: string;
    submitted_at?: string;
}

interface Props {
    applications: {
        data: Application[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { status?: string; search?: string };
}

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' | 'info'> = {
    draft: 'outline',
    submitted: 'info',
    under_review: 'warning',
    approved: 'success',
    rejected: 'destructive',
    converted: 'default',
    withdrawn: 'outline',
};

export default function AdmissionsApplicationsIndex({ applications, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (status: string) => {
        router.get('/admissions/applications', { status, search }, { preserveState: true });
    };

    return (
        <AppShell
            title="Applications"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Admissions', href: '/admissions/applications' },
            ]}
        >
            <PageHeader
                title="Applications"
                description="Review and manage admission applications"
                actions={<Button>Export</Button>}
            />

            <div className="mt-6 rounded-lg border bg-card">
                <div className="flex flex-wrap items-center gap-4 border-b p-4">
                    <form
                        className="relative flex-1 min-w-[220px]"
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get('/admissions/applications', { status: filters.status ?? '', search }, { preserveState: true });
                        }}
                    >
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search applications..."
                            className="pl-9"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </form>
                    <div className="flex flex-wrap gap-2">
                        {['', 'submitted', 'under_review', 'approved', 'rejected', 'converted'].map((status) => (
                            <button
                                key={status || 'all'}
                                onClick={() => applyFilter(status)}
                                className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
                                    (filters.status ?? '') === status
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {status === '' ? 'All' : status.replace('_', ' ')}
                            </button>
                        ))}
                    </div>
                </div>

                {applications.data.length === 0 ? (
                    <EmptyState
                        title="No applications found"
                        description="Applications will appear here once submitted."
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-start font-medium">Reference</th>
                                    <th className="px-4 py-3 text-start font-medium">Student</th>
                                    <th className="px-4 py-3 text-start font-medium">Grade</th>
                                    <th className="px-4 py-3 text-start font-medium">Status</th>
                                    <th className="px-4 py-3 text-start font-medium">Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                {applications.data.map((application) => (
                                    <tr key={application.id} className="border-b last:border-0 hover:bg-muted/40">
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`/admissions/applications/${application.id}`}
                                                className="text-primary hover:underline"
                                            >
                                                {application.reference}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {application.student_first_name} {application.student_last_name}
                                        </td>
                                        <td className="px-4 py-3">{application.grade_applying ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={statusVariant[application.status] ?? 'secondary'}>
                                                {application.status.replace('_', ' ')}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {application.submitted_at
                                                ? new Date(application.submitted_at).toLocaleDateString()
                                                : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {applications.last_page > 1 && (
                    <div className="flex flex-wrap items-center gap-1 border-t p-4">
                        {applications.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                className={`rounded px-3 py-1 text-sm ${
                                    link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'
                                } disabled:opacity-50`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppShell>
    );
}
