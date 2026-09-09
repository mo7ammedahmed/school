import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function DocumentsEdit({ document }: { document: { id: number; title: string; classification: string; description: string } }) {
    return (
        <AppShell
            title="Edit Document"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Documents', href: '/documents' },
                { label: 'Edit Document' },
            ]}
        >
            <PageHeader
                title="Edit Document"
                description={document.title}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/documents"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Document Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/documents/${document.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Document Name</Label>
                                <Input id="title" name="title" defaultValue={document.title} required />
                            </div>
                            <div>
                                <Label htmlFor="classification">Document Type</Label>
                                <select id="classification" name="classification" className="input" required defaultValue={document.classification}>
                                    <option value="transcript">Transcript</option>
                                    <option value="certificate">Certificate</option>
                                    <option value="report">Report</option>
                                    <option value="policy">Policy</option>
                                    <option value="form">Form</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <Input id="description" name="description" defaultValue={document.description} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/documents">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Document</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
