import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type AcademicYear = { id: number; name: string };

type Props = {
    academicYears: AcademicYear[];
};

export default function SemestersCreate({ academicYears }: Props) {
    return (
        <AppShell
            title="Add Semester"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Semesters', href: '/semesters' },
                { label: 'Add Semester' },
            ]}
        >
            <PageHeader
                title="Add Semester"
                description="Create a semester inside one of your academic years"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/semesters">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Semester Information</CardTitle>
                    <CardDescription>
                        Fill in one language — the other is translated for you automatically.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/semesters">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_ar">Semester Name (Arabic)</Label>
                                <Input id="name_ar" name="name_ar" dir="rtl" placeholder="مثال: الفصل الدراسي الأول" />
                            </div>
                            <div>
                                <Label htmlFor="name_en">Semester Name (English)</Label>
                                <Input id="name_en" name="name_en" required placeholder="e.g., First Semester" />
                                <TranslatePair enId="name_en" arId="name_ar" />
                            </div>
                            <div>
                                <Label htmlFor="academic_year_id">Academic Year</Label>
                                <select
                                    id="academic_year_id"
                                    name="academic_year_id"
                                    className="input"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Select an academic year
                                    </option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>
                                            {year.name}
                                        </option>
                                    ))}
                                </select>
                                {academicYears.length === 0 && (
                                    <p className="mt-1 text-xs text-destructive">
                                        Create an academic year first — a semester has to belong to one.
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="code">Code</Label>
                                <Input id="code" name="code" required placeholder="e.g., S1" />
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input id="start_date" name="start_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input id="end_date" name="end_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="is_current">Current Semester</Label>
                                <select id="is_current" name="is_current" className="input" defaultValue="0">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/semesters">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Semester</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
