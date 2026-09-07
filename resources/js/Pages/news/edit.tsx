import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function NewsEdit({ article }: { article: { id: number; title: string; category: string; publish_date: string; content: string; is_published: boolean } }) {
    return (
        <AppShell
            title="Edit Article"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'News', href: '/news' },
                { label: 'Edit Article' },
            ]}
        >
            <PageHeader
                title="Edit Article"
                description={article.title}
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
                    <form className="space-y-6" method="POST" action={`/content/news/${article.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" defaultValue={article.title} required />
                            </div>
                            <div>
                                <Label htmlFor="category">Category</Label>
                                <select id="category" name="category" className="input" required defaultValue={article.category}>
                                    <option value="general">General</option>
                                    <option value="academic">Academic</option>
                                    <option value="sports">Sports</option>
                                    <option value="events">Events</option>
                                    <option value="announcements">Announcements</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="publish_date">Publish Date</Label>
                                <Input id="publish_date" name="publish_date" type="date" defaultValue={article.publish_date} required />
                            </div>
                            <div>
                                <Label htmlFor="is_published">Published</Label>
                                <select id="is_published" name="is_published" className="input" required defaultValue={String(article.is_published)}>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="content">Content</Label>
                                <textarea id="content" name="content" className="input min-h-[200px]" required defaultValue={article.content} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/content/news">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Article</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
