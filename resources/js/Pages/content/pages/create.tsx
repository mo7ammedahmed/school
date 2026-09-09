import { Link, useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type SectionType = { value: string; label: string };
type ExistingPage = {
    id: number;
    title: string;
    title_ar?: string | null;
    slug: string;
    content?: string | null;
    template: string;
    sections?: { type: string; enabled: boolean; content: Record<string, string>; settings: Record<string, string> }[] | null;
    status: string;
    published_at?: string | null;
    scheduled_at?: string | null;
    seo_title?: string | null;
    seo_description?: string | null;
    canonical_url?: string | null;
    robots: string;
};

export default function PageCreate({ sectionTypes, page }: { sectionTypes: SectionType[]; page?: ExistingPage }) {
    const { data, setData, post, put, processing, errors } = useForm({
        title: page?.title ?? '',
        title_ar: page?.title_ar ?? '',
        slug: page?.slug ?? '',
        content: page?.content ?? '',
        template: page?.template ?? 'standard',
        sections: page?.sections ?? [] as { type: string; enabled: boolean; content: Record<string, string>; settings: Record<string, string> }[],
        status: page?.status ?? 'draft',
        published_at: page?.published_at ?? '',
        scheduled_at: page?.scheduled_at ?? '',
        seo_title: page?.seo_title ?? '',
        seo_description: page?.seo_description ?? '',
        canonical_url: page?.canonical_url ?? '',
        robots: page?.robots ?? 'index,follow',
    });

    const addSection = (type: string) => setData('sections', [...data.sections, { type, enabled: true, content: {}, settings: {} }]);

    return (
        <AppShell title="New Website Page" breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Website Pages', href: '/content/pages' }, { label: 'New page' }]}>
            <PageHeader title="New Website Page" description="Compose a public page from controlled content sections." />
            <form onSubmit={(event) => { event.preventDefault(); page ? put(`/content/pages/${page.id}`) : post('/content/pages'); }} className="mt-6 space-y-6">
                <Card><CardHeader><CardTitle>Page details</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-2">
                    {(['title', 'title_ar', 'slug', 'seo_title', 'seo_description', 'canonical_url'] as const).map((field) => (
                        <div key={field} className={field === 'seo_description' ? 'md:col-span-2' : ''}>
                            <Label htmlFor={field}>{field.replaceAll('_', ' ')}</Label>
                            <Input id={field} value={data[field]} onChange={(event) => setData(field, event.target.value)} />
                            {errors[field] && <p className="text-sm text-destructive">{errors[field]}</p>}
                        </div>
                    ))}
                    <div><Label htmlFor="status">Status</Label><select id="status" className="input w-full" value={data.status} onChange={(event) => setData('status', event.target.value)}><option value="draft">Draft</option><option value="published">Published</option><option value="scheduled">Scheduled</option></select></div>
                    <div><Label htmlFor="template">Template</Label><select id="template" className="input w-full" value={data.template} onChange={(event) => setData('template', event.target.value)}><option value="standard">Standard</option><option value="landing">Landing</option></select></div>
                </CardContent></Card>
                <Card><CardHeader><CardTitle>Public content</CardTitle></CardHeader><CardContent>
                    <Label htmlFor="content">Page content</Label>
                    <textarea id="content" value={data.content} onChange={(event) => setData('content', event.target.value)} className="input min-h-40 w-full" placeholder="Write the public page content..." />
                    {errors.content && <p className="text-sm text-destructive">{errors.content}</p>}
                </CardContent></Card>
                <Card><CardHeader><CardTitle>Sections</CardTitle></CardHeader><CardContent className="space-y-3">
                    <div className="flex flex-wrap gap-2">{sectionTypes.map((section) => <Button key={section.value} type="button" variant="outline" onClick={() => addSection(section.value)}>Add {section.label}</Button>)}</div>
                    {data.sections.map((section, index) => <div key={`${section.type}-${index}`} className="space-y-3 rounded-lg border p-3 text-sm">
                        <div className="flex items-center justify-between"><span>{index + 1}. {section.type.replace('_', ' ')}</span><Button type="button" variant="ghost" size="sm" onClick={() => setData('sections', data.sections.filter((_, itemIndex) => itemIndex !== index))}>Remove</Button></div>
                        <Input placeholder="Section title" value={section.content.title ?? ''} onChange={(event) => setData('sections', data.sections.map((item, itemIndex) => itemIndex === index ? { ...item, content: { ...item.content, title: event.target.value } } : item))} />
                        <textarea placeholder="Section description" value={section.content.description ?? ''} onChange={(event) => setData('sections', data.sections.map((item, itemIndex) => itemIndex === index ? { ...item, content: { ...item.content, description: event.target.value } } : item))} className="input min-h-24 w-full" />
                    </div>)}
                </CardContent></Card>
                <div className="flex gap-3"><Button type="button" variant="outline" asChild><Link href="/content/pages">Cancel</Link></Button><Button type="submit" disabled={processing}>{page ? 'Save changes' : 'Create page'}</Button></div>
            </form>
        </AppShell>
    );
}
