import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useThemeMode } from '@/lib/theme';
import { useLocale } from '@/lib/i18n/locale-context';

type ThemeToggleProps = {
    variant?: 'ghost' | 'outline';
    className?: string;
};

/**
 * Flips between the light and dark palettes. The resolved mode is what matters
 * to the user, so a user on "system" sees a single toggle that pins whichever
 * mode they were already looking at.
 */
export function ThemeToggle({ variant = 'ghost', className }: ThemeToggleProps) {
    const { resolved, toggle } = useThemeMode();
    const { locale } = useLocale();
    const isDark = resolved === 'dark';

    const label = isDark
        ? locale === 'ar'
            ? 'التبديل إلى الوضع الفاتح'
            : 'Switch to light mode'
        : locale === 'ar'
          ? 'التبديل إلى الوضع الداكن'
          : 'Switch to dark mode';

    return (
        <Button
            variant={variant}
            size="icon-sm"
            onClick={toggle}
            className={className}
            aria-label={label}
            title={label}
            aria-pressed={isDark}
        >
            {isDark ? (
                <Sun className="size-[1.1rem]" aria-hidden="true" />
            ) : (
                <Moon className="size-[1.1rem]" aria-hidden="true" />
            )}
        </Button>
    );
}
