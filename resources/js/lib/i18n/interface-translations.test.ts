import { describe, expect, it } from 'vitest';
import raw from '../../../../database/seeders/data/interface-translations-ar.json?raw';
import { handWrittenArabic } from './copy';

/**
 * The hand-written Arabic dictionary that ships with the app.
 *
 * The seeder reads the same file, but the checks that are cheaper in
 * JavaScript — has a pair drifted into a string copy.ts already translates, is
 * a value still English — live here. The keys themselves come from
 * `node scripts/extract-interface-strings.mjs`; this file guards the pairs.
 */
/** Names that are already their own Arabic: brands and product names. */
const KEEP_AS_IS = new Set(['Stripe', 'Hyperpay', 'Moyasar', 'SchoolOS', 'PDF']);

const pairs = JSON.parse(raw) as Record<string, string>;

describe('the hand-written Arabic dictionary', () => {
    it('covers the app, rather than a sample of it', () => {
        expect(Object.keys(pairs).length).toBeGreaterThanOrEqual(1000);
    });

    it('writes Arabic for every string that is not a proper name', () => {
        for (const [english, arabic] of Object.entries(pairs)) {
            expect(arabic.trim(), english).not.toBe('');

            if (KEEP_AS_IS.has(english)) {
                continue;
            }

            expect(arabic, english).not.toBe(english);
            expect(/[\u0600-\u06ff]/.test(arabic), english).toBe(true);
        }
    });

    it('does not repeat the pairs copy.ts already translates by hand', () => {
        const handWritten = handWrittenArabic();

        for (const english of Object.keys(pairs)) {
            // Not `toHaveProperty`: a string with a full stop in it — and many
            // labels here have one — reads as a property path there.
            expect(Object.prototype.hasOwnProperty.call(handWritten, english), english).toBe(false);
        }
    });
});
