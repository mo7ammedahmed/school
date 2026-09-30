import { t, type CopyKey, type Locale } from '@/lib/i18n/copy';

type BadgeVariant =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'outline'
    | 'success'
    | 'warning'
    | 'error'
    | 'info'
    | 'neutral';

const LABELS: Record<string, CopyKey> = {
    draft: 'finance.invoices.status.draft',
    issued: 'finance.invoices.status.issued',
    partially_paid: 'finance.invoices.status.partially_paid',
    paid: 'finance.invoices.status.paid',
    overdue: 'finance.invoices.status.overdue',
    void: 'finance.invoices.status.void',
};

/**
 * The list and the detail screen both printed the raw column value, so an Arabic
 * interface read "partially_paid" while the translated labels sat unused in the
 * copy dictionary, and `partially_paid` — the status a finance officer has to
 * recognise — had no label at all.
 *
 * Unknown statuses fall back to the readable column value rather than an empty
 * badge.
 */
export function invoiceStatusLabel(locale: Locale, status: string): string {
    const key = LABELS[status];

    return key ? t(locale, key) : status.replace(/_/g, ' ');
}

/** One definition instead of the same five branches copied into two screens. */
export function invoiceStatusVariant(status: string): BadgeVariant {
    if (status === 'paid') return 'success';
    if (status === 'issued') return 'info';
    if (status === 'partially_paid') return 'warning';
    if (status === 'void') return 'destructive';

    return 'secondary';
}
