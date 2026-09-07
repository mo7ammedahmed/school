import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SubjectsShow({ subject }: { subject: { id: number; name: string; code: string; grade_level: { name: string }; credits: number } }) {
    return (
        <AppShell
            title="Subject Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Subjects', href: '/subjects' },
                { label: subject.name },
            ]}
        >
            <PageHeader
                title="Subject Details"
                description={subject.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/subjects"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/subjects/${subject.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Subject Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject Name</span>
                            <p className="text-base">{subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Code</span>
                            <p className="text-base">{subject.code}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Grade Level</span>
                            <p className="text-base">{subject.grade_level.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Credits</span>
                            <p className="text-base">{subject.credits || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
