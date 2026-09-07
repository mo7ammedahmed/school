import PublicLayout from '@/layouts/public-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useState } from 'react';
import { t, tk } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface FAQItem {
    question: string;
    answer: string;
    category: string;
}

interface FaqProps {
    faqs: Record<string, FAQItem[]>;
}

function AccordionItem({
    question,
    answer,
    isOpen,
    onClick,
}: {
    question: string;
    answer: string;
    isOpen: boolean;
    onClick: () => void;
}) {
    return (
        <div className="rounded-lg border border-gray-200 dark:border-gray-700">
            <button
                onClick={onClick}
                className="flex w-full items-center justify-between p-4 text-left font-medium transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                <span className="text-gray-900 dark:text-white">{question}</span>
                <span className="ml-4 flex-shrink-0 text-gray-500 dark:text-gray-400">{isOpen ? '−' : '+'}</span>
            </button>
            {isOpen && (
                <div className="border-t border-gray-200 dark:border-gray-700 p-4">
                    <p className="text-gray-600 dark:text-gray-300">{answer}</p>
                </div>
            )}
        </div>
    );
}

export default function FAQ({ faqs }: FaqProps) {
    const { locale } = useLocale();
    const [openIndex, setOpenIndex] = useState<string | null>(null);

    const categories = Object.keys(faqs);

    return (
        <PublicLayout>
            <div className="min-h-screen bg-white dark:bg-gray-900">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                                {t(locale, 'public.faq')}
                            </h1>
                            <p className="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                                {t(locale, 'public.faqDescription')}
                            </p>
                        </div>

                        <div className="grid gap-8 lg:grid-cols-4">
                            <div className="lg:col-span-1">
                                <Card className="border-0 shadow-lg bg-gray-50 dark:bg-gray-800 sticky top-24">
                                    <CardHeader>
                                        <CardTitle>{t(locale, 'public.categories')}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <nav className="space-y-2">
                                            {categories.map((category) => (
                                                <a
                                                    key={category}
                                                    href={`#${category.toLowerCase()}`}
                                                    className="block rounded-md px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 hover:text-emerald-600 dark:hover:text-emerald-400"
                                                >
                                                    {tk(locale, `public.faq.category.${category}`)}
                                                </a>
                                            ))}
                                        </nav>
                                    </CardContent>
                                </Card>
                            </div>

                            <div className="space-y-8 lg:col-span-3">
                                {categories.map((category) => {
                                    const categoryFaqs = faqs[category];
                                    return (
                                        <div key={category} id={category.toLowerCase()}>
                                            <h2 className="mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                                                {tk(locale, `public.faq.category.${category}`)}
                                            </h2>
                                            <div className="space-y-4">
                                                {categoryFaqs.map((faq, index) => {
                                                    const globalKey = `${category}-${index}`;
                                                    return (
                                                        <AccordionItem
                                                            key={globalKey}
                                                            question={tk(locale, faq.question)}
                                                            answer={tk(locale, faq.answer)}
                                                            isOpen={openIndex === globalKey}
                                                            onClick={() =>
                                                                setOpenIndex(openIndex === globalKey ? null : globalKey)
                                                            }
                                                        />
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
