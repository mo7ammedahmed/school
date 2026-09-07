import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AcademicYearsCreate() {
    return (
        <AppShell
            title="Add Academic Year"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Academic Years', href: '/academic-years' },
                { label: 'Add Academic Year' },
            ]}
        >
            <PageHeader
                title="Add Academic Year"
                description="Create a new academic year"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/academic-years"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Academic Year Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/academic-years">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Academic Year Name</Label>
                                <Input id="name" name="name" placeholder="e.g., 2024-2025" required />
                            </div>
                            <div>
                                <Label htmlFor="is_current">Current Year</Label>
                                <select id="is_current" name="is_current" className="input" required defaultValue="0">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input id="start_date" name="start_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input id="end_date" name="end_date" type="date" required />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/academic-years">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Academic Year</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
