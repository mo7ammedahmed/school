export type ContentItem = {
    title?: string | null;
    title_ar?: string | null;
    description?: string | null;
    description_ar?: string | null;
    url?: string | null;
};

export type WebsiteSection = {
    type: string;
    enabled: boolean;
    content: ContentItem & {
        button_label?: string | null;
        button_label_ar?: string | null;
        button_url?: string | null;
        items?: ContentItem[];
    };
    settings: { limit?: number };
};

export type WebsitePage = {
    id?: number;
    title: string;
    title_ar?: string | null;
    slug: string;
    content?: string | null;
    content_ar?: string | null;
    template: string;
    sections?: WebsiteSection[] | null;
    status?: string;
    published_at?: string | null;
    scheduled_at?: string | null;
    seo_title?: string | null;
    seo_description?: string | null;
    canonical_url?: string | null;
    robots: string;
    show_in_navigation?: boolean;
    navigation_order?: number;
};

export type WebsiteLocation = {
    slug: string;
    url: string;
    title: string;
    title_ar: string;
};

export function translatedText(
    english: string | null | undefined,
    arabic: string | null | undefined,
    locale: string,
): string {
    return locale === 'ar' ? arabic || english || '' : english || arabic || '';
}

// Old stored content also passes through this check, so a legacy unsafe URL
// cannot bypass the server validation added to the editor.
export function safeWebsiteUrl(value: string | null | undefined): string | undefined {
    if (!value || Array.from(value).some((character) => character.charCodeAt(0) <= 32 || character === '\\'))
        return undefined;
    if (value.startsWith('/') && !value.startsWith('//')) return value;
    try {
        const url = new URL(value);
        return url.protocol === 'https:' ? value : undefined;
    } catch {
        return undefined;
    }
}

export function moveSection<T>(sections: T[], index: number, direction: -1 | 1): T[] {
    const target = index + direction;
    if (index < 0 || index >= sections.length || target < 0 || target >= sections.length) return sections;
    const next = [...sections];
    [next[index], next[target]] = [next[target], next[index]];
    return next;
}
