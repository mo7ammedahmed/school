import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SubjectsEdit({ subject, gradeLevels }: { subject: { id: number; name: string; name_ar: string | null; name_en: string | null; code: string; grade_level_id: number | null; credits: number | null }; gradeLevels: { id: number; name: string }[] }) {
    const s = subject;

    return (
        <AppShell
            title="Edit Subject"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Subjects', href: '/subjects' },
                { label: 'Edit Subject' },
            ]}
        >
            <PageHeader
                title="Edit Subject"
                description={s.name_en ?? s.name ?? ''}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/subjects"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Subject Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/subjects/${subject.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_ar">Subject Name (Arabic)</Label>
                                <Input id="name_ar" name="name_ar" defaultValue={s.name_ar ?? ''} required />
                            </div>
                            <div>
                                <Label htmlFor="name_en">Subject Name (English)</Label>
                                <Input id="name_en" name="name_en" defaultValue={s.name_en ?? ''} required />
                            </div>
                            <div>
                                <Label htmlFor="code">Subject Code</Label>
                                <Input id="code" name="code" defaultValue={s.code} required />
                            </div>
                            <div>
                                <Label htmlFor="grade_level_id">Grade Level</Label>
                                <select id="grade_level_id" name="grade_level_id" className="input" required defaultValue={s.grade_level_id ?? ''}>
                                    <option value="">Select grade level</option>
                                    {gradeLevels.map((grade) => (
                                        <option key={grade.id} value={grade.id}>{grade.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="credits">Credits</Label>
                                <Input id="credits" name="credits" type="number" step="0.1" defaultValue={s.credits ?? ''} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/subjects">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Subject</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}


