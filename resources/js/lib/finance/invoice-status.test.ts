import { describe, expect, it } from 'vitest';
import { invoiceStatusLabel, invoiceStatusVariant } from './invoice-status';

describe('invoice status labels', () => {
    it('reads the status in the language the interface is using', () => {
        expect(invoiceStatusLabel('en', 'draft')).toBe('Draft');
        expect(invoiceStatusLabel('ar', 'draft')).toBe('مسودة');
    });

    it('has a label for partially paid, the status that had none', () => {
        expect(invoiceStatusLabel('en', 'partially_paid')).toBe('Partially paid');
        expect(invoiceStatusLabel('ar', 'partially_paid')).toBe('مدفوعة جزئيًا');
    });

    it('falls back to a readable value rather than an empty badge for an unknown status', () => {
        expect(invoiceStatusLabel('en', 'written_off_by_hand')).toBe('written off by hand');
    });
});

describe('invoice status variants', () => {
    it('maps the settled and unsettled statuses apart', () => {
        expect(invoiceStatusVariant('paid')).toBe('success');
        expect(invoiceStatusVariant('issued')).toBe('info');
        expect(invoiceStatusVariant('partially_paid')).toBe('warning');
        expect(invoiceStatusVariant('void')).toBe('destructive');
        expect(invoiceStatusVariant('draft')).toBe('secondary');
    });
});
