import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AcademicYearsEdit({ academicYear }: { academicYear: { id: number; name: string; name_ar: string; name_en: string; start_date: string; end_date: string; is_current: boolean } }) {
    return (
        <AppShell
            title="Edit Academic Year"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Academic Years', href: '/academic-years' },
                { label: 'Edit Academic Year' },
            ]}
        >
            <PageHeader
                title="Edit Academic Year"
                description={academicYear.name}
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
                    <form className="space-y-6" method="POST" action={`/academic-years/${academicYear.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_ar">Academic Year Name (Arabic)</Label>
                                <Input id="name_ar" name="name_ar" defaultValue={academicYear.name_ar} required />
                            </div>
                            <div>
                                <Label htmlFor="name_en">Academic Year Name (English)</Label>
                                <Input id="name_en" name="name_en" defaultValue={academicYear.name_en} required />
                            </div>
                            <div>
                                <Label htmlFor="is_current">Current Year</Label>
                                <select id="is_current" name="is_current" className="input" required defaultValue={String(academicYear.is_current)}>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input id="start_date" name="start_date" type="date" defaultValue={academicYear.start_date} required />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input id="end_date" name="end_date" type="date" defaultValue={academicYear.end_date} required />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/academic-years">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Academic Year</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
