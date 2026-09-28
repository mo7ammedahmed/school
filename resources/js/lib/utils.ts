import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export { clsx };
export type { ClassValue };

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatCurrency(amount: number, currency: string = 'SAR', locale: string = 'en') {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function formatDate(date: string | Date, locale: string = 'en', options?: Intl.DateTimeFormatOptions) {
    return new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        ...options,
    }).format(new Date(date));
}

export function formatNumber(value: number, locale: string = 'en') {
    return new Intl.NumberFormat(locale).format(value);
}

export function isRTL(locale: string): boolean {
    return locale === 'ar';
}

/**
 * Turn a role slug such as `super_admin` into `Super Admin` for display.
 */
export function humaniseRole(role: string | null | undefined): string {
    if (!role) return '—';

    return role.replace(/[_-]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function truncate(text: string, length: number): string {
    if (text.length <= length) return text;
    return text.slice(0, length) + '...';
}

export function debounce<T extends (...args: unknown[]) => unknown>(
    func: T,
    wait: number
): (...args: Parameters<T>) => void {
    // `ReturnType<typeof setTimeout>` keeps this correct in both the browser and
    // Node without pulling in the @types/node globals.
    let timeout: ReturnType<typeof setTimeout> | null = null;
    return (...args: Parameters<T>) => {
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}
