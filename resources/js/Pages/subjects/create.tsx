import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SubjectsCreate({ gradeLevels }: { gradeLevels: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="Add Subject"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Subjects', href: '/subjects' },
                { label: 'Add Subject' },
            ]}
        >
            <PageHeader
                title="Add Subject"
                description="Create a new subject"
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
                    <form className="space-y-6" method="POST" action="/subjects">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Subject Name</Label>
                                <Input id="name" name="name" required />
                            </div>
                            <div>
                                <Label htmlFor="code">Subject Code</Label>
                                <Input id="code" name="code" required />
                            </div>
                            <div>
                                <Label htmlFor="grade_level_id">Grade Level</Label>
                                <select id="grade_level_id" name="grade_level_id" className="input" required>
                                    <option value="">Select grade level</option>
                                    {gradeLevels.map((grade) => (
                                        <option key={grade.id} value={grade.id}>{grade.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="credits">Credits</Label>
                                <Input id="credits" name="credits" type="number" step="0.1" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/subjects">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Subject</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
