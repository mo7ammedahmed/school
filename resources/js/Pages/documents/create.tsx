import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function DocumentsCreate() {
    return (
        <AppShell
            title="Upload Document"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Documents', href: '/documents' },
                { label: 'Upload Document' },
            ]}
        >
            <PageHeader
                title="Upload Document"
                description="Add a new document"
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
                    <form className="space-y-6" method="POST" action="/documents" encType="multipart/form-data">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Document Name</Label>
                                <Input id="name" name="name" required />
                            </div>
                            <div>
                                <Label htmlFor="document_type">Document Type</Label>
                                <select id="document_type" name="document_type" className="input" required>
                                    <option value="">Select type</option>
                                    <option value="transcript">Transcript</option>
                                    <option value="certificate">Certificate</option>
                                    <option value="report">Report</option>
                                    <option value="policy">Policy</option>
                                    <option value="form">Form</option>
                                    <option value="other">Other</option>
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
                                <Link href="/documents">Cancel</Link>
                            </Button>
                            <Button type="submit">Upload Document</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
