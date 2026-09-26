import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type AcademicYear = { id: number; name: string };

type Semester = {
    id: number;
    name_ar: string | null;
    name_en: string | null;
    academic_year_id: number;
    code: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
};

type Props = {
    semester: Semester;
    academicYears: AcademicYear[];
};

export default function SemestersEdit({ semester, academicYears }: Props) {
    return (
        <AppShell
            title="Edit Semester"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Semesters', href: '/semesters' },
                { label: semester.name_en ?? semester.code },
            ]}
        >
            <PageHeader
                title="Edit Semester"
                description={semester.name_en ?? semester.code}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={`/semesters/${semester.id}`}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Semester Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/semesters/${semester.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_ar">Semester Name (Arabic)</Label>
                                <Input
                                    id="name_ar"
                                    name="name_ar"
                                    dir="rtl"
                                    defaultValue={semester.name_ar ?? ''}
                                />
                            </div>
                            <div>
                                <Label htmlFor="name_en">Semester Name (English)</Label>
                                <Input
                                    id="name_en"
                                    name="name_en"
                                    required
                                    defaultValue={semester.name_en ?? ''}
                                />
                                <TranslatePair enId="name_en" arId="name_ar" />
                            </div>
                            <div>
                                <Label htmlFor="academic_year_id">Academic Year</Label>
                                <select
                                    id="academic_year_id"
                                    name="academic_year_id"
                                    className="input"
                                    required
                                    defaultValue={semester.academic_year_id}
                                >
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>
                                            {year.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="code">Code</Label>
                                <Input id="code" name="code" required defaultValue={semester.code} />
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    required
                                    defaultValue={semester.start_date?.slice(0, 10)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    name="end_date"
                                    type="date"
                                    required
                                    defaultValue={semester.end_date?.slice(0, 10)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="is_current">Current Semester</Label>
                                <select
                                    id="is_current"
                                    name="is_current"
                                    className="input"
                                    defaultValue={semester.is_current ? '1' : '0'}
                                >
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/semesters/${semester.id}`}>Cancel</Link>
                            </Button>
                            <Button type="submit">Save Changes</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
