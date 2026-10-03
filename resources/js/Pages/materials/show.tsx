import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Download } from 'lucide-react';
import { formatDuration } from '@/lib/media/format';
import { Link } from '@inertiajs/react';

export default function MaterialsShow({ material }: {    material: { id: number; title: string; subject: { name: string }; section: { name: string }; file_type: string; file_path: string | null; description: string; file_size: number; kind: string; duration_seconds: number | null; uploaded_at: string } }) {
    const streamable = (material.kind === 'video' || material.kind === 'recording') && material.file_path;
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
                            <Link href="/materials"><ArrowLeft className="me-2 h-4 w-4" />Back</Link>
                        </Button>
                        {/* The file is on the private disk, so this is the only way
                            to get it. */}
                        {material.file_path && (
                            <Button variant="outline" asChild>
                                <a href={`/materials/${material.id}/download`}>
                                    <Download className="me-2 h-4 w-4" />Download
                                </a>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href={`/materials/${material.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            {streamable && (
                <Card className="mb-6 overflow-hidden">
                    <CardHeader>
                        <CardTitle>Lesson video</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {/* Streamed from the private disk with Range support, so
                            seeking works without exposing a file URL. */}
                        <video
                            className="aspect-video w-full rounded-lg border border-border/70 bg-black"
                            controls
                            playsInline
                            preload="metadata"
                            src={`/materials/${material.id}/stream`}
                        />
                    </CardContent>
                </Card>
            )}

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
                            <span className="text-sm font-medium text-muted-foreground">Kind</span>
                            <p className="text-base capitalize">{material.kind}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">File Size</span>
                            <p className="text-base">{material.file_size ? `${(material.file_size / 1024).toFixed(1)} KB` : '-'}</p>
                        </div>
                        {material.kind !== 'file' && (
                            <div>
                                <span className="text-sm font-medium text-muted-foreground">Duration</span>
                                <p className="text-base tabular-nums">{formatDuration(material.duration_seconds)}</p>
                            </div>
                        )}
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
