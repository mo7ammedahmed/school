import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useBilingual } from '@/lib/i18n/bilingual';

type Student = { id: number; first_name: string | null; last_name: string | null };
type AcademicYear = { id: number; name_en: string | null; name_ar: string | null };

export default function ReportCardsCreate({ students, academicYears }: { students: Student[]; academicYears: AcademicYear[] }) {
    const bilingual = useBilingual();

    return (
        <AppShell
            title="New Report Card"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Report Cards', href: '/report-cards' },
                { label: 'New Report Card' },
            ]}
        >
            <PageHeader
                title="New Report Card"
                description="Record a student's GPA and a comment in both languages"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/report-cards"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Report Card Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/report-cards">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required defaultValue="">
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>
                                            {student.first_name} {student.last_name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="academic_year_id">Academic Year</Label>
                                <select id="academic_year_id" name="academic_year_id" className="input" required defaultValue="">
                                    <option value="">Select academic year</option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>
                                            {bilingual(year.name_en, year.name_ar)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="gpa">GPA</Label>
                                <Input id="gpa" name="gpa" type="number" step="0.01" min="0" max="4" required />
                            </div>
                            <div>
                                <Label htmlFor="is_published">Published</Label>
                                <select id="is_published" name="is_published" className="input" required defaultValue="0">
                                    <option value="0">No — keep as a draft</option>
                                    <option value="1">Yes — visible to the guardian</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="comments">Comments (English)</Label>
                                <textarea id="comments" name="comments" className="input min-h-[120px]" />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="comments_ar">Comments (Arabic)</Label>
                                <textarea id="comments_ar" name="comments_ar" dir="rtl" className="input min-h-[120px]" />
                            </div>
                            <div className="md:col-span-2">
                                <TranslatePair enId="comments" arId="comments_ar" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/report-cards">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Report Card</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
