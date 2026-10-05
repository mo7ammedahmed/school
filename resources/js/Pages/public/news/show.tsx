import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Calendar } from 'lucide-react';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface NewsArticle {
    id: number;
    title: string;
    /** The model appends `publish_date`; the column is `published_at`. */
    publish_date: string | null;
    content: string;
    excerpt: string;
    category: string;
    featured_image_path?: string | null;
}

interface NewsShowProps {
    article: NewsArticle;
}

export default function NewsShow({ article }: NewsShowProps) {
    const { locale } = useLocale();
    // `new Date(undefined)` is not a date: it printed "Invalid Date", because
    // the page read `date` and the payload carries `publish_date`.
    const published = article.publish_date
        ? new Date(article.publish_date).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : '';

    return (
        <PublicLayout>
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-4xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/news">
                                <ArrowLeft className="me-2 h-4 w-4" />
                                {t(locale, 'public.backToNews')}
                            </Link>
                        </Button>

                        <Card className="border-0 shadow-lg bg-card text-card-foreground overflow-hidden">
                            {article.featured_image_path && (
                                <div className="relative h-64 w-full overflow-hidden sm:h-80">
                                    <img
                                        src={`/storage/${article.featured_image_path}`}
                                        alt={article.title}
                                        className="h-full w-full object-cover"
                                    />
                                    <div className="absolute bottom-4 start-4">
                                        <span className="inline-flex items-center gap-1 rounded-full bg-card/90 px-3 py-1 text-sm text-card-foreground">
                                            <Calendar className="h-4 w-4" />
                                            {published}
                                        </span>
                                    </div>
                                </div>
                            )}
                            <CardHeader>
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span className="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary dark:text-pine-200">
                                        {article.category}
                                    </span>
                                    <span>{published}</span>
                                </div>
                                <CardTitle className="text-3xl md:text-4xl">{article.title}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="prose dark:prose-invert max-w-none">
                                    {article.content.split('\n').map((paragraph: string, index: number) => (
                                        <p key={index} className="mb-4 text-card-foreground leading-relaxed">
                                            {paragraph}
                                        </p>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
