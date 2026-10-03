import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Radio } from 'lucide-react';
import { Link } from '@inertiajs/react';

type Offering = {
    id: number;
    name: string;
    subject: string | null;
    section: string | null;
};

export default function LiveCreate({ offerings }: { offerings: Offering[] }) {
    return (
        <AppShell
            title="Start a Live Session"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Live Lessons', href: '/live' },
                { label: 'Start a session' },
            ]}
        >
            <PageHeader
                title="Start a Live Session"
                description="Pick one of your subjects, then broadcast camera or screen from the studio"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/live">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Session information</CardTitle>
                </CardHeader>
                <CardContent>
                    {offerings.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            You have no offerings assigned yet. A session belongs to a subject and section, and an
                            administrator assigns offerings to teachers.
                        </p>
                    ) : (
                        <form className="space-y-6" method="POST" action="/live">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="title">Title</Label>
                                    <Input id="title" name="title" required placeholder="Mathematics — revision session" />
                                </div>
                                <div>
                                    <Label htmlFor="offering_id">Subject & section</Label>
                                    <select id="offering_id" name="offering_id" className="input" required defaultValue="">
                                        <option value="" disabled>
                                            Select an offering
                                        </option>
                                        {offerings.map((offering) => (
                                            <option key={offering.id} value={offering.id}>
                                                {offering.subject ?? offering.name}
                                                {offering.section ? ` — ${offering.section}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <p className="text-sm text-muted-foreground">
                                The lesson is recorded automatically. When you end the session, the recording is added to
                                the subject&apos;s materials for your students.
                            </p>

                            <div className="flex gap-4">
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/live">Cancel</Link>
                                </Button>
                                <Button type="submit">
                                    <Radio className="me-2 h-4 w-4" />
                                    Create and open studio
                                </Button>
                            </div>
                        </form>
                    )}
                </CardContent>
            </Card>
        </AppShell>
    );
}
