import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState } from '@/components/ui/empty-state';
import { Plus, Search } from 'lucide-react';

export default function SemestersIndex() {
    const semesters = [
        { id: 1, name: 'First Semester', code: 'S1', start_date: '2025-09-01', end_date: '2026-01-31', is_current: true },
        { id: 2, name: 'Second Semester', code: 'S2', start_date: '2026-02-01', end_date: '2026-06-30', is_current: false },
    ];

    return (
        <AppShell
            title="Semesters"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Academic Years', href: '/academic-years' },
                { label: 'Semesters' },
            ]}
        >
            <PageHeader
                title="Semesters"
                description="Manage semesters for the academic year"
                actions={
                    <Button>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Semester
                    </Button>
                }
            />

            <div className="mt-6 rounded-lg border bg-card">
                <div className="flex items-center gap-4 border-b p-4">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search semesters..."
                            className="pl-9"
                        />
                    </div>
                </div>

                {semesters.length === 0 ? (
                    <EmptyState
                        title="No semesters found"
                        description="Get started by adding your first semester."
                        action={<Button>Add Semester</Button>}
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-left font-medium">Name</th>
                                    <th className="px-4 py-3 text-left font-medium">Code</th>
                                    <th className="px-4 py-3 text-left font-medium">Start Date</th>
                                    <th className="px-4 py-3 text-left font-medium">End Date</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {semesters.map((semester) => (
                                    <tr key={semester.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">{semester.name}</td>
                                        <td className="px-4 py-3">{semester.code}</td>
                                        <td className="px-4 py-3">{semester.start_date}</td>
                                        <td className="px-4 py-3">{semester.end_date}</td>
                                        <td className="px-4 py-3">
                                            {semester.is_current ? (
                                                <span className="inline-flex rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-medium text-success">
                                                    Current
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
                                                    Inactive
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppShell>
    );
}
