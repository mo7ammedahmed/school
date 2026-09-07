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
    date: string;
    content: string;
    excerpt: string;
    category: string;
    featured_image?: string;
}

interface NewsShowProps {
    article: NewsArticle;
}

export default function NewsShow({ article }: NewsShowProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-4xl mx-auto">
                        <Button asChild variant="outline" className="mb-8">
                            <Link href="/news">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t(locale, 'public.backToNews')}
                            </Link>
                        </Button>

                        <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 overflow-hidden">
                            {article.featured_image && (
                                <div className="relative h-64 w-full overflow-hidden sm:h-80">
                                    <img
                                        src={article.featured_image}
                                        alt={article.title}
                                        className="h-full w-full object-cover"
                                    />
                                    <div className="absolute bottom-4 left-4">
                                        <span className="inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-sm text-gray-900">
                                            <Calendar className="h-4 w-4" />
                                            {new Date(article.date).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })}
                                        </span>
                                    </div>
                                </div>
                            )}
                            <CardHeader>
                                <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span className="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        {article.category}
                                    </span>
                                    <span>{new Date(article.date).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}</span>
                                </div>
                                <CardTitle className="text-3xl md:text-4xl">{article.title}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="prose prose-gray dark:prose-invert max-w-none">
                                    {article.content.split('\n').map((paragraph: string, index: number) => (
                                        <p key={index} className="mb-4 text-gray-700 dark:text-gray-300 leading-relaxed">
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
