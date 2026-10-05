import { describe, expect, it } from 'vitest';
import { moveSection, safeWebsiteUrl, translatedText } from './website-content';

describe('public website content', () => {
    it('keeps unsafe and malformed links out of public content', () => {
        for (const value of [
            'javascript:alert(1)',
            'data:text/html,x',
            '//outside.example',
            '/\\outside.example',
            'https://',
            ' https://example.com',
            '/a\nscript',
            'http://example.com',
        ]) {
            expect(safeWebsiteUrl(value)).toBeUndefined();
        }
        expect(safeWebsiteUrl('/apply')).toBe('/apply');
        expect(safeWebsiteUrl('https://example.com/tour')).toBe('https://example.com/tour');
    });
    it('reorders sections without mutating or losing hidden content', () => {
        const sections = [
            { type: 'hero', enabled: true },
            { type: 'faq', enabled: false },
        ];
        expect(moveSection(sections, 1, -1)).toEqual([sections[1], sections[0]]);
        expect(sections[0].type).toBe('hero');
        expect(moveSection(sections, 0, -1)).toBe(sections);
        expect(moveSection(sections, 1, 1)).toBe(sections);
    });
    it('falls back to the authored language when a translation is empty', () => {
        expect(translatedText('', 'المدرسة', 'en')).toBe('المدرسة');
        expect(translatedText('School', '', 'ar')).toBe('School');
    });
});
