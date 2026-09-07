import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function MaterialsCreate({ subjects, sections }: { subjects: { id: number; name: string }[]; sections: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="Upload Material"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Materials', href: '/materials' },
                { label: 'Upload Material' },
            ]}
        >
            <PageHeader
                title="Upload Material"
                description="Add a new learning material"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/materials"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Material Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/materials" encType="multipart/form-data">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" required />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Subject</Label>
                                <select id="subject_id" name="subject_id" className="input" required>
                                    <option value="">Select subject</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>{subject.name}</option>
                                    ))}
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
                                <Label htmlFor="file">File</Label>
                                <Input id="file" name="file" type="file" required />
                            </div>
                            <div>
                                <Label htmlFor="description">Description</Label>
                                <Input id="description" name="description" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/materials">Cancel</Link>
                            </Button>
                            <Button type="submit">Upload Material</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
