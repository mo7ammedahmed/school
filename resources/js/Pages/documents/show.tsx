import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function DocumentsShow({ document }: { document: { id: number; name: string; document_type: string; file_path: string; file_size: string; description: string; uploaded_by: string; uploaded_at: string } }) {
    return (
        <AppShell
            title="Document Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Documents', href: '/documents' },
                { label: document.name },
            ]}
        >
            <PageHeader
                title="Document Details"
                description={document.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/documents"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/documents/${document.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Document Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Document Name</span>
                            <p className="text-base">{document.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Document Type</span>
                            <p className="text-base capitalize">{document.document_type.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">File Size</span>
                            <p className="text-base">{document.file_size || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Uploaded By</span>
                            <p className="text-base">{document.uploaded_by}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Uploaded At</span>
                            <p className="text-base">{document.uploaded_at}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="text-base">{document.description || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
