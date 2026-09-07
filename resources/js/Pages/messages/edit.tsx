import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function MessagesEdit({ message }: { message: { id: number; subject: string; content: string } }) {
    return (
        <AppShell
            title="Edit Message"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Messages', href: '/messages' },
                { label: 'Edit Message' },
            ]}
        >
            <PageHeader
                title="Edit Message"
                description={message.subject}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/messages"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Message Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/messages/${message.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6">
                            <div>
                                <Label htmlFor="subject">Subject</Label>
                                <Input id="subject" name="subject" defaultValue={message.subject} required />
                            </div>
                            <div>
                                <Label htmlFor="content">Message</Label>
                                <textarea id="content" name="content" className="input min-h-[150px]" required defaultValue={message.content} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/messages">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Message</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
