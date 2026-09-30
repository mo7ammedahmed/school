import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { translateInterfaceCopy } from './ui-copy';

// The cells hold the elements the walker actually visits (`span`, `p`, …), which
// is how the dashboard renders a badge or a status inside a table cell.
const cell = (content: string): string => `<td><span>${content}</span></td>`;

vi.mock('@inertiajs/react', () => ({
    router: {
        on: () => () => undefined,
    },
}));

/**
 * Table cells were skipped outright as "data", which left every status and
 * payment method the dashboard prints untranslated — the one English island on
 * an otherwise Arabic screen. A cell is still data: a value the dictionary does
 * not know is left exactly as the school wrote it and is never sent to the
 * provider.
 */
let fetchMock: ReturnType<typeof vi.fn>;

function sentStrings(): string[] {
    return fetchMock.mock.calls
        .filter(([url]) => String(url).includes('/ui/copy'))
        .flatMap(([, options]) => JSON.parse(String((options as RequestInit)?.body ?? '{}')).strings ?? []);
}

describe('interface copy inside table cells', () => {
    beforeEach(() => {
        window.localStorage.clear();
        document.body.innerHTML = '';

        fetchMock = vi.fn(async () => ({
            ok: true,
            json: async () => ({ version: 'test', translations: {} }),
        }));

        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        document.body.innerHTML = '';
    });

    it('translates a machine value the screen printed as text', () => {
        document.body.innerHTML = `
            <table><tbody><tr>
                ${cell('Bank Transfer')}
                ${cell('Draft')}
                ${cell('Partially Paid')}
            </tr></tbody></table>
        `;

        const undo = translateInterfaceCopy('ar', null);

        expect(document.body.textContent).toContain('تحويل بنكي');
        expect(document.body.textContent).toContain('مسودة');
        expect(document.body.textContent).toContain('مدفوعة جزئيًا');

        undo();

        expect(document.body.textContent).toContain('Bank Transfer');
        expect(document.body.textContent).toContain('Draft');
    });

    it('translates a machine value written straight into a cell', () => {
        // Most columns render their text directly rather than inside a span.
        document.body.innerHTML = `
            <table><tbody><tr>
                <td>bank_transfer</td>
                <td>Cash</td>
            </tr></tbody></table>
        `;

        const undo = translateInterfaceCopy('ar', null);

        expect(document.body.textContent).toContain('تحويل بنكي');
        expect(document.body.textContent).toContain('نقدًا');

        undo();

        expect(document.body.textContent).toContain('bank_transfer');
    });

    it('leaves what the school entered exactly as it was typed', () => {
        document.body.innerHTML = `
            <table><tbody><tr>
                ${cell('Amal Rahman')}
                ${cell('1,000.00 SAR')}
                ${cell('ملاحظة من المدرسة')}
            </tr></tbody></table>
        `;

        const undo = translateInterfaceCopy('ar', null);

        expect(document.body.textContent).toContain('Amal Rahman');
        expect(document.body.textContent).toContain('1,000.00 SAR');
        expect(document.body.textContent).toContain('ملاحظة من المدرسة');

        undo();
    });

    it('never sends a table cell to the translation provider', async () => {
        document.body.innerHTML = `
            <table><tbody><tr>${cell('Some value this school invented')}</tr></tbody></table>
            <p>An unseen heading</p>
        `;

        const undo = translateInterfaceCopy('ar', null);

        await vi.waitFor(() => expect(sentStrings().length).toBeGreaterThan(0), { timeout: 2000 });

        expect(sentStrings()).toContain('An unseen heading');
        expect(sentStrings()).not.toContain('Some value this school invented');

        undo();
    });
});
