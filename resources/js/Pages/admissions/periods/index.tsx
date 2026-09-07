import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState } from '@/components/ui/empty-state';
import { Plus, Search } from 'lucide-react';

interface Period {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    applications_count: number;
}

export default function AdmissionsPeriodsIndex({ periods }: { periods: Period[] }) {
    return (
        <AppShell
            title="Admission Periods"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Admissions', href: '/admissions/applications' },
                { label: 'Periods' },
            ]}
        >
            <PageHeader
                title="Admission Periods"
                description="Manage admission periods"
                actions={
                    <Button>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Period
                    </Button>
                }
            />

            <div className="mt-6 rounded-lg border bg-card">
                <div className="flex items-center gap-4 border-b p-4">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search periods..." className="pl-9" />
                    </div>
                </div>

                {periods.length === 0 ? (
                    <EmptyState
                        title="No admission periods found"
                        description="Get started by creating your first admission period."
                        action={<Button>Add Period</Button>}
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-start font-medium">Name</th>
                                    <th className="px-4 py-3 text-start font-medium">Start Date</th>
                                    <th className="px-4 py-3 text-start font-medium">End Date</th>
                                    <th className="px-4 py-3 text-start font-medium">Applications</th>
                                    <th className="px-4 py-3 text-start font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {periods.map((period) => (
                                    <tr key={period.id} className="border-b last:border-0 hover:bg-muted/40">
                                        <td className="px-4 py-3">{period.name}</td>
                                        <td className="px-4 py-3">{period.start_date}</td>
                                        <td className="px-4 py-3">{period.end_date}</td>
                                        <td className="px-4 py-3">{period.applications_count}</td>
                                        <td className="px-4 py-3">
                                            {period.is_active ? (
                                                <span className="inline-flex rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-medium text-success">
                                                    Active
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
