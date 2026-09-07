import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AssessmentsCreate({ sections }: { sections: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="New Assessment"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assessments', href: '/assessments' },
                { label: 'New Assessment' },
            ]}
        >
            <PageHeader
                title="New Assessment"
                description="Create a new assessment"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/assessments"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Assessment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/assessments">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Assessment Name</Label>
                                <Input id="name" name="name" required />
                            </div>
                            <div>
                                <Label htmlFor="assessment_type">Assessment Type</Label>
                                <select id="assessment_type" name="assessment_type" className="input" required>
                                    <option value="">Select type</option>
                                    <option value="formative">Formative</option>
                                    <option value="summative">Summative</option>
                                    <option value="diagnostic">Diagnostic</option>
                                    <option value="benchmark">Benchmark</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="section_id">Section</Label>
                                <select id="section_id" name="section_id" className="input" required>
                                    <option value="">Select section</option>
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id}>{section.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="assessment_date">Assessment Date</Label>
                                <Input id="assessment_date" name="assessment_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="total_marks">Total Marks</Label>
                                <Input id="total_marks" name="total_marks" type="number" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="scheduled">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea id="description" name="description" className="input min-h-[100px]" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/assessments">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Assessment</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
