import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    [
        'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium',
        'leading-none whitespace-nowrap transition-colors',
    ].join(' '),
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                destructive: 'border-transparent bg-destructive/10 text-destructive',
                outline: 'border-border bg-card text-foreground',
                success: 'border-transparent bg-success/10 text-success',
                warning: 'border-transparent bg-warning/12 text-warning',
                error: 'border-transparent bg-error/10 text-error',
                info: 'border-transparent bg-info/10 text-info',
                neutral: 'border-border/60 bg-muted/60 text-muted-foreground',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export interface BadgeProps
    extends React.HTMLAttributes<HTMLDivElement>,
        VariantProps<typeof badgeVariants> {}

export function Badge({ className, variant, ...props }: BadgeProps) {
    return <span className={cn(badgeVariants({ variant }), className)} {...props} />;
}
