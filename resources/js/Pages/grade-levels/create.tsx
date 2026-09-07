import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GradeLevelsCreate() {
    return (
        <AppShell
            title="Add Grade Level"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Grade Levels', href: '/grade-levels' },
                { label: 'Add Grade Level' },
            ]}
        >
            <PageHeader
                title="Add Grade Level"
                description="Create a new grade level"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/grade-levels"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Grade Level Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/grade-levels">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Grade Name</Label>
                                <Input id="name" name="name" required />
                            </div>
                            <div>
                                <Label htmlFor="level">Level</Label>
                                <Input id="level" name="level" type="number" required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea id="description" name="description" className="input min-h-[100px]" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/grade-levels">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Grade Level</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
