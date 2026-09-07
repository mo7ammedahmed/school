import { Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t, type Locale } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import { cn } from '@/lib/utils';

interface LanguageSwitcherProps {
    /** Visual style used by the public website header. */
    variant?: 'default' | 'outline' | 'ghost';
    className?: string;
}

/**
 * Toggles the whole interface between English (LTR) and Arabic (RTL),
 * mirroring the reference bilingual implementation: an icon button whose
 * label is the name of the language it switches *to*.
 */
export function LanguageSwitcher({ variant = 'ghost', className }: LanguageSwitcherProps) {
    const { locale, toggleLocale } = useLocale();

    const target: Locale = locale === 'ar' ? 'en' : 'ar';
    const label = t(locale, target === 'ar' ? 'lang.switchToAr' : 'lang.switchToEn');
    const ariaLabel = target === 'ar' ? 'Switch language to Arabic' : 'التبديل إلى اللغة الإنجليزية';

    return (
        <Button
            type="button"
            variant={variant}
            size="sm"
            onClick={toggleLocale}
            aria-label={ariaLabel}
            title={label}
            className={cn('gap-2', className)}
        >
            <Languages className="size-4" aria-hidden="true" />
            <span>{label}</span>
        </Button>
    );
}
