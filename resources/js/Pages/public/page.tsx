import { Head } from '@inertiajs/react';

type Section = { type: string; enabled: boolean; content?: Record<string, string>; settings?: Record<string, string> };
type Page = { title: string; title_ar?: string | null; content?: string | null; sections?: Section[] | null; seo_title?: string | null; seo_description?: string | null; canonical_url?: string | null; robots: string };

function SectionView({ section }: { section: Section }) {
    if (!section.enabled) return null;
    const title = section.content?.title ?? '';
    const description = section.content?.description ?? '';

    if (section.type === 'cta' || section.type === 'hero') {
        return <section className="mx-auto max-w-6xl px-6 py-20"><h1 className="text-4xl font-semibold tracking-tight">{title}</h1>{description && <p className="mt-4 max-w-2xl text-muted-foreground">{description}</p>}</section>;
    }
    if (section.type === 'rich_text') {
        return <section className="mx-auto max-w-3xl px-6 py-10"><h2 className="text-2xl font-semibold">{title}</h2>{description && <p className="mt-3 whitespace-pre-line text-muted-foreground">{description}</p>}</section>;
    }
    return <section className="mx-auto max-w-6xl px-6 py-10"><h2 className="text-2xl font-semibold">{title || section.type.replace('_', ' ')}</h2></section>;
}

export default function PublicPage({ page }: { page: Page }) {
    return (
        <>
            <Head title={page.seo_title || page.title}><meta name="description" content={page.seo_description || undefined} /><meta name="robots" content={page.robots} />{page.canonical_url && <link rel="canonical" href={page.canonical_url} />}</Head>
            <main><header className="mx-auto max-w-6xl px-6 py-12"><h1 className="text-4xl font-semibold">{page.title}</h1>{page.title_ar && <p lang="ar" dir="rtl" className="mt-2 text-muted-foreground">{page.title_ar}</p>}</header>{page.content && <div className="mx-auto max-w-3xl px-6 whitespace-pre-line">{page.content}</div>}{(page.sections ?? []).map((section, index) => <SectionView key={`${section.type}-${index}`} section={section} />)}</main>
        </>
    );
}
