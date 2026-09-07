import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SectionsEdit({ section, gradeLevels, academicYears }: { section: { id: number; name: string; grade_level_id: number; academic_year_id: number; capacity: number }; gradeLevels: { id: number; name: string }[]; academicYears: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="Edit Section"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Sections', href: '/sections' },
                { label: 'Edit Section' },
            ]}
        >
            <PageHeader
                title="Edit Section"
                description={section.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/sections"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Section Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/sections/${section.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Section Name</Label>
                                <Input id="name" name="name" defaultValue={section.name} required />
                            </div>
                            <div>
                                <Label htmlFor="grade_level_id">Grade Level</Label>
                                <select id="grade_level_id" name="grade_level_id" className="input" required defaultValue={section.grade_level_id}>
                                    <option value="">Select grade level</option>
                                    {gradeLevels.map((grade) => (
                                        <option key={grade.id} value={grade.id}>{grade.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="academic_year_id">Academic Year</Label>
                                <select id="academic_year_id" name="academic_year_id" className="input" required defaultValue={section.academic_year_id}>
                                    <option value="">Select academic year</option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>{year.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="capacity">Capacity</Label>
                                <Input id="capacity" name="capacity" type="number" defaultValue={section.capacity} required />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/sections">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Section</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
