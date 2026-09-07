import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function SectionsShow({ section }: { section: { id: number; name: string; grade_level: { name: string }; academic_year: { name: string }; capacity: number } }) {
    return (
        <AppShell
            title="Section Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Sections', href: '/sections' },
                { label: section.name },
            ]}
        >
            <PageHeader
                title="Section Details"
                description={section.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/sections"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/sections/${section.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Section Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section Name</span>
                            <p className="text-base">{section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Grade Level</span>
                            <p className="text-base">{section.grade_level.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Academic Year</span>
                            <p className="text-base">{section.academic_year.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Capacity</span>
                            <p className="text-base">{section.capacity}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
