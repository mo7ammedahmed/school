import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import * as React from 'react';

const buttonVariants = cva(
    [
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium',
        'tracking-[-0.006em] transition-all duration-200 ease-out',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60 focus-visible:ring-offset-2 focus-visible:ring-offset-background',
        'disabled:pointer-events-none disabled:opacity-50',
        '[&_svg]:pointer-events-none [&_svg]:shrink-0',
        'active:translate-y-px',
    ].join(' '),
    {
        variants: {
            variant: {
                default: [
                    'bg-primary text-primary-foreground shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_1px_2px_rgba(6,40,30,0.28)]',
                    'hover:bg-primary/92 hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_2px_6px_rgba(6,40,30,0.3)]',
                ],
                secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/85',
                outline: [
                    'border border-input bg-card/60 text-foreground shadow-[inset_0_0_0_1px_rgba(255,255,255,0.4)]',
                    'hover:border-foreground/20 hover:bg-accent',
                ],
                ghost: 'text-muted-foreground hover:bg-accent hover:text-foreground',
                destructive: 'bg-destructive text-white shadow-sm hover:bg-destructive/90',
                link: 'text-primary underline-offset-4 hover:underline',
            },
            size: {
                sm: 'h-9 px-3 text-[0.8125rem]',
                md: 'h-10 px-4 text-sm',
                lg: 'h-12 px-6 text-[0.9375rem]',
                icon: 'size-10',
                'icon-sm': 'size-9',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'md',
        },
    }
);

export interface ButtonProps
    extends React.ButtonHTMLAttributes<HTMLButtonElement>,
        VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ variant, size, asChild = false, className, children, ...props }, ref) => {
        const Comp = asChild ? Slot : 'button';
        return (
            <Comp ref={ref} className={cn(buttonVariants({ variant, size }), className)} {...props}>
                {children}
            </Comp>
        );
    }
);
Button.displayName = 'Button';
