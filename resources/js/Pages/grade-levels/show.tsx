import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GradeLevelsShow({ gradeLevel }: { gradeLevel: { id: number; name: string; level: number; description: string } }) {
    return (
        <AppShell
            title="Grade Level Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Grade Levels', href: '/grade-levels' },
                { label: gradeLevel.name },
            ]}
        >
            <PageHeader
                title="Grade Level Details"
                description={gradeLevel.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/grade-levels"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/grade-levels/${gradeLevel.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Grade Level Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Grade Name</span>
                            <p className="text-base">{gradeLevel.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Level</span>
                            <p className="text-base">{gradeLevel.level}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="text-base">{gradeLevel.description || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
