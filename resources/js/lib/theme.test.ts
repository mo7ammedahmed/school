import { afterEach, describe, expect, it } from 'vitest';
import { applyTheme, DEFAULT_PALETTES } from './theme';

const root = () => document.documentElement;
const value = (name: string) => root().style.getPropertyValue(name);

afterEach(() => {
    applyTheme('light', DEFAULT_PALETTES);
    root().removeAttribute('style');
    root().classList.remove('dark');
});

describe('school palette transitions', () => {
    it('clears light surface overrides when switching to dark, then restores them on return', () => {
        const website = {
            colorBackground: '#fff5ee',
            colorCard: '#fff0e0',
            colorHeader: '#221144',
            colorFooter: '#442211',
            colorButtonSecondary: '#eeddaa',
            colorPrimary: '#7744cc',
        };

        applyTheme('light', DEFAULT_PALETTES, website);
        expect(value('--color-card')).toBe('#fff0e0');
        expect(value('--color-header')).toBe('#221144');

        applyTheme('dark', DEFAULT_PALETTES, website);
        expect(root().classList.contains('dark')).toBe(true);
        expect(value('--color-background')).toBe('#070707');
        expect(value('--color-card')).toBe('#0b0b0b');
        // Empty inline values allow the dark CSS header/footer/button defaults.
        expect(value('--color-header')).toBe('');
        expect(value('--color-footer')).toBe('');
        expect(value('--color-button-secondary')).toBe('');
        expect(value('--color-primary')).toBe('#7744cc');

        applyTheme('light', DEFAULT_PALETTES, website);
        expect(value('--color-card')).toBe('#fff0e0');
        expect(value('--color-header')).toBe('#221144');
        expect(root().classList.contains('dark')).toBe(false);
    });

    it('removes overrides when the school palette changes or is cleared', () => {
        applyTheme('light', DEFAULT_PALETTES, {
            colorCard: '#ddeeff',
            colorHeader: '#112233',
            colorFooter: '#221133',
            colorPrimary: '#bb2255',
        });
        applyTheme('light', DEFAULT_PALETTES, { colorCard: '#ffeedd' });
        expect(value('--color-card')).toBe('#ffeedd');
        expect(value('--color-header')).toBe('');
        expect(value('--color-footer')).toBe('');
        expect(value('--color-primary')).toBe('#006c55');

        applyTheme('dark', DEFAULT_PALETTES);
        expect(value('--color-card')).toBe('#0b0b0b');
    });
});
