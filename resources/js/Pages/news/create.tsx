import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function NewsCreate() {
    return (
        <AppShell
            title="New Article"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'News', href: '/news' },
                { label: 'New Article' },
            ]}
        >
            <PageHeader
                title="New Article"
                description="Create a news article"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/content/news"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Article Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/content/news">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" required />
                            </div>
                            <div>
                                <Label htmlFor="category">Category</Label>
                                <select id="category" name="category" className="input" required>
                                    <option value="general">General</option>
                                    <option value="academic">Academic</option>
                                    <option value="sports">Sports</option>
                                    <option value="events">Events</option>
                                    <option value="announcements">Announcements</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="publish_date">Publish Date</Label>
                                <Input id="publish_date" name="publish_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="is_published">Published</Label>
                                <select id="is_published" name="is_published" className="input" required defaultValue="0">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="content">Content</Label>
                                <textarea id="content" name="content" className="input min-h-[200px]" required />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/content/news">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Article</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
