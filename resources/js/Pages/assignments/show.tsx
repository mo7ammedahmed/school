import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AssignmentsShow({ assignment }: { assignment: { id: number; title: string; subject: { name: string }; section: { name: string }; due_date: string; total_marks: number; description: string; status: string } }) {
    return (
        <AppShell
            title="Assignment Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assignments', href: '/assignments' },
                { label: assignment.title },
            ]}
        >
            <PageHeader
                title="Assignment Details"
                description={assignment.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/assignments"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/assignments/${assignment.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Assignment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Title</span>
                            <p className="text-base">{assignment.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Subject</span>
                            <p className="text-base">{assignment.subject.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{assignment.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Due Date</span>
                            <p className="text-base">{assignment.due_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Total Marks</span>
                            <p className="text-base">{assignment.total_marks}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base capitalize">{assignment.status}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="text-base whitespace-pre-wrap">{assignment.description || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
