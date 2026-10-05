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
        <div className="rounded-lg border border-border">
            <button
                onClick={onClick}
                className="flex w-full items-center justify-between p-4 text-start font-medium transition-colors hover:bg-accent"
            >
                <span className="text-foreground">{question}</span>
                <span className="ms-4 flex-shrink-0 text-muted-foreground">{isOpen ? '−' : '+'}</span>
            </button>
            {isOpen && (
                <div className="border-t border-border p-4">
                    <p className="text-muted-foreground">{answer}</p>
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
            <div className="min-h-screen bg-background text-foreground">
                <section className="py-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
                                {t(locale, 'public.faq')}
                            </h1>
                            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
                                {t(locale, 'public.faqDescription')}
                            </p>
                        </div>

                        <div className="grid gap-8 lg:grid-cols-4">
                            <div className="lg:col-span-1">
                                <Card className="border-0 shadow-lg bg-card text-card-foreground sticky top-24">
                                    <CardHeader>
                                        <CardTitle>{t(locale, 'public.categories')}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <nav className="space-y-2">
                                            {categories.map((category) => (
                                                <a
                                                    key={category}
                                                    href={`#${category.toLowerCase()}`}
                                                    className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-primary/10 hover:text-primary"
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
                                            <h2 className="mb-6 text-2xl font-semibold text-foreground">
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
