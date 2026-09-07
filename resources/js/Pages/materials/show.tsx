import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function MaterialsShow({ material }: {    material: { id: number; title: string; subject: { name: string }; section: { name: string }; file_type: string; file_path: string; description: string; file_size: number; uploaded_at: string } }) {
    return (
        <AppShell
            title="Material Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Materials', href: '/materials' },
                { label: material.title },
            ]}
        >
            <PageHeader
                title="Material Details"
                description={material.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/materials"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/materials/${material.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Material Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Title</span>
                            <p className="text-base">{material.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject</span>
                            <p className="text-base">{material.subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{material.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">File Type</span>
                            <p className="text-base uppercase">{material.file_type}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">File Size</span>
                            <p className="text-base">{material.file_size ? `${(material.file_size / 1024).toFixed(1)} KB` : '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Uploaded At</span>
                            <p className="text-base">{material.uploaded_at}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="text-base">{material.description || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
