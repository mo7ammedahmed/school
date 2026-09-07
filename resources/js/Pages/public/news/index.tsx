import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Calendar, ArrowLeft } from 'lucide-react';
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

interface NewsIndexProps {
    articles: {
        data: NewsArticle[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function NewsIndex({ articles }: NewsIndexProps) {
    const { locale } = useLocale();

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.news')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.newsDescription')}
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-12">
                            {articles.data.map((article) => (
                                <Card key={article.id} className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col">
                                    {article.featured_image && (
                                        <div className="relative h-48 w-full overflow-hidden">
                                            <img
                                                src={article.featured_image}
                                                alt={article.title}
                                                className="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
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
                                        <CardTitle className="text-xl line-clamp-2">{article.title}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="flex-1 flex flex-col">
                                        <p className="text-gray-600 dark:text-gray-300 line-clamp-3 mb-4 flex-1">{article.excerpt}</p>
                                        <Button asChild variant="outline" className="w-full mt-auto">
                                            <Link href={`/news/${article.id}`}>{t(locale, 'public.readMore')}</Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Pagination */}
                        {articles.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {articles.current_page > 1 && (
                                    <Button variant="outline" asChild>
                                        <Link href={`/news?page=${articles.current_page - 1}`}>
                                            <ArrowLeft className="mr-2 h-4 w-4" />
                                            {t(locale, 'public.previous')}
                                        </Link>
                                    </Button>
                                )}
                                <span className="text-gray-600 dark:text-gray-400 px-4">
                                    {t(locale, 'public.page', { current: articles.current_page, total: articles.last_page })}
                                </span>
                                {articles.current_page < articles.last_page && (
                                    <Button variant="outline" asChild>
                                        <Link href={`/news?page=${articles.current_page + 1}`}>
                                            {t(locale, 'public.next')}
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}