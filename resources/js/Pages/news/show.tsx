import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function NewsShow({ article }: { article: { id: number; title: string; category: string; publish_date: string; content: string; is_published: boolean } }) {
    return (
        <AppShell
            title="Article Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'News', href: '/content/news' },
                { label: article.title },
            ]}
        >
            <PageHeader
                title="Article Details"
                description={article.title}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/content/news"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/content/news/${article.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Article Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Title</span>
                            <p className="text-base">{article.title}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Category</span>
                            <p className="text-base capitalize">{article.category.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Publish Date</span>
                            <p className="text-base">{article.publish_date}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Status</span>
                            <p className="text-base">{article.is_published ? 'Published' : 'Draft'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Content</span>
                            <p className="text-base whitespace-pre-wrap">{article.content}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
