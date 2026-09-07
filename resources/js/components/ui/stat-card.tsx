import { TrendingDown, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';

interface StatCardProps {
    title: string;
    value: string | number;
    description?: string;
    trend?: { value: number; label: string };
    icon?: React.ReactNode;
    className?: string;
    accentClassName?: string;
}

export function StatCard({
    title,
    value,
    description,
    trend,
    icon,
    className,
    accentClassName = 'bg-primary/8 text-primary',
}: StatCardProps) {
    const positive = (trend?.value ?? 0) >= 0;

    return (
        <div
            className={cn(
                'group relative overflow-hidden rounded-xl border border-border/80 bg-card p-5',
                'shadow-[var(--shadow-sm)] transition-shadow duration-300 hover:shadow-[var(--shadow-md)]',
                className
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-[0.8125rem] font-medium text-muted-foreground">
                        {title}
                    </p>
                    <p className="mt-2.5 text-[1.75rem] font-semibold leading-none tracking-[-0.02em] tabular-nums text-foreground">
                        {value}
                    </p>
                </div>
                {icon && (
                    <div
                        className={cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-lg',
                            accentClassName
                        )}
                    >
                        {icon}
                    </div>
                )}
            </div>

            {(description || trend) && (
                <div className="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
                    {trend && (
                        <span
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[0.6875rem] font-semibold',
                                positive ? 'bg-success/10 text-success' : 'bg-error/10 text-error'
                            )}
                        >
                            {positive ? (
                                <TrendingUp className="size-3" aria-hidden="true" />
                            ) : (
                                <TrendingDown className="size-3" aria-hidden="true" />
                            )}
                            {positive ? '+' : ''}
                            {trend.value}%
                        </span>
                    )}
                    {description && (
                        <span className="text-xs text-muted-foreground">{description}</span>
                    )}
                    {trend?.label && (
                        <span className="text-xs text-muted-foreground">· {trend.label}</span>
                    )}
                </div>
            )}
        </div>
    );
}
