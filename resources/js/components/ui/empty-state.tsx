import { cn } from '@/lib/utils';

interface EmptyStateProps {
    title: string;
    description?: string;
    action?: React.ReactNode;
    icon?: React.ReactNode;
    className?: string;
}

export function EmptyState({ title, description, action, icon, className }: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center px-6 py-16 text-center',
                className
            )}
        >
            <div className="relative mb-5">
                <div className="flex size-14 items-center justify-center rounded-2xl border border-border/80 bg-secondary/70 text-muted-foreground shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]">
                    {icon}
                </div>
                <span
                    aria-hidden="true"
                    className="absolute -end-1 -top-1 size-2.5 rounded-full bg-gold-300/80"
                />
            </div>
            <h3 className="text-[0.9375rem] font-semibold text-foreground">{title}</h3>
            {description && (
                <p className="mt-1.5 max-w-sm text-sm leading-relaxed text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
