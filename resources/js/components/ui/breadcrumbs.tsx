import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface BreadcrumbsProps {
    items: BreadcrumbItem[];
    className?: string;
}

export function Breadcrumbs({ items, className }: BreadcrumbsProps) {
    const { locale } = useLocale();

    return (
        <nav
            aria-label={t(locale, 'a11y.breadcrumb')}
            className={cn('flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground', className)}
        >
            {items.map((item, index) => (
                <span key={index} className="flex items-center gap-1.5">
                    {index > 0 && (
                        <ChevronRight aria-hidden="true" className="size-3.5 text-muted-foreground/50 rtl:-scale-x-100" />
                    )}
                    {item.href ? (
                        <a href={item.href} className="transition-colors hover:text-foreground">
                            {item.label}
                        </a>
                    ) : (
                        <span className="text-xs font-medium text-foreground/60">{item.label}</span>
                    )}
                </span>
            ))}
        </nav>
    );
}
