import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState } from '@/components/ui/empty-state';
import { Plus, Search } from 'lucide-react';

export default function ReportsIndex() {
    const reports = [
        { id: 1, name: 'Student Enrollment Report', type: 'students', generated_at: '2025-09-01' },
        { id: 2, name: 'Attendance Summary', type: 'attendance', generated_at: '2025-09-02' },
        { id: 3, name: 'Financial Report', type: 'finance', generated_at: '2025-09-03' },
    ];

    return (
        <AppShell
            title="Reports"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Reports' },
            ]}
        >
            <PageHeader
                title="Reports"
                description="Generate and download reports"
                actions={
                    <Button>
                        <Plus className="mr-2 h-4 w-4" />
                        Generate Report
                    </Button>
                }
            />

            <div className="mt-6 rounded-lg border bg-card">
                <div className="flex items-center gap-4 border-b p-4">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search reports..."
                            className="pl-9"
                        />
                    </div>
                </div>

                {reports.length === 0 ? (
                    <EmptyState
                        title="No reports found"
                        description="Generate your first report."
                        action={<Button>Generate Report</Button>}
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-left font-medium">Name</th>
                                    <th className="px-4 py-3 text-left font-medium">Type</th>
                                    <th className="px-4 py-3 text-left font-medium">Generated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reports.map((report) => (
                                    <tr key={report.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">{report.name}</td>
                                        <td className="px-4 py-3">{report.type}</td>
                                        <td className="px-4 py-3">{report.generated_at}</td>
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
