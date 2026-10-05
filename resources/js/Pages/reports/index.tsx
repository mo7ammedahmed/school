import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { EmptyState } from '@/components/ui/empty-state';
import { Search } from 'lucide-react';

type Report = { id: number; name: string; type: string; generated_at: string };

export default function ReportsIndex({ reports = [] }: { reports?: Report[] }) {
    const [search, setSearch] = useState('');
    const filteredReports = reports.filter((report) => report.name.toLowerCase().includes(search.toLowerCase()));

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
                description="Generated reports available to your account"
            />

            <div className="mt-6 rounded-lg border bg-card">
                <div className="flex items-center gap-4 border-b p-4">
                    <div className="relative flex-1">
                        <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search reports..."
                            className="ps-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                </div>

                {filteredReports.length === 0 ? (
                    <EmptyState
                        title="No reports found"
                        description={search ? 'Try a different search.' : 'There are no generated reports available.'}
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-start font-medium">Name</th>
                                    <th className="px-4 py-3 text-start font-medium">Type</th>
                                    <th className="px-4 py-3 text-start font-medium">Generated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredReports.map((report) => (
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
