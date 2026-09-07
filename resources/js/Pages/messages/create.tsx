import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function MessagesCreate() {
    return (
        <AppShell
            title="New Message"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Messages', href: '/messages' },
                { label: 'New Message' },
            ]}
        >
            <PageHeader
                title="New Message"
                description="Start a school conversation"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/messages"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Compose</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/messages">
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="subject">Subject</Label>
                                <Input id="subject" name="subject" required placeholder="e.g. Staff meeting reminders" />
                            </div>
                            <div>
                                <Label htmlFor="body">Message</Label>
                                <textarea id="body" name="body" className="input min-h-[140px]" required placeholder="Write your message…" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/messages">Cancel</Link>
                            </Button>
                            <Button type="submit">Send Message</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
