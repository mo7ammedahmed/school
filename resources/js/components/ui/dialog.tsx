import { forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface DialogProps extends React.DialogHTMLAttributes<HTMLDialogElement> {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export const Dialog = forwardRef<HTMLDialogElement, DialogProps>(
    ({ className, open, onOpenChange, children, ...props }, ref) => {
        return (
            <dialog
                ref={ref}
                open={open}
                onClose={onOpenChange ? () => onOpenChange(false) : undefined}
                className={cn(
                    'm-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-border/80 bg-card p-0 text-card-foreground shadow-[var(--shadow-panel)]',
                    'backdrop:bg-ink/30 backdrop:backdrop-blur-[2px]',
                    className
                )}
                {...props}
            >
                {children}
            </dialog>
        );
    }
);
Dialog.displayName = 'Dialog';

export const DialogContent = forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, children, ...props }, ref) => {
        return (
            <div ref={ref} className={cn('p-6', className)} {...props}>
                {children}
            </div>
        );
    }
);
DialogContent.displayName = 'DialogContent';

export const DialogHeader = forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => {
        return <div ref={ref} className={cn('mb-5 space-y-1.5', className)} {...props} />;
    }
);
DialogHeader.displayName = 'DialogHeader';

export const DialogTitle = forwardRef<HTMLHeadingElement, React.HTMLAttributes<HTMLHeadingElement>>(
    ({ className, ...props }, ref) => {
        return (
            <h2
                ref={ref}
                className={cn('text-lg font-semibold tracking-[-0.01em] text-foreground', className)}
                {...props}
            />
        );
    }
);
DialogTitle.displayName = 'DialogTitle';

export const DialogDescription = forwardRef<HTMLParagraphElement, React.HTMLAttributes<HTMLParagraphElement>>(
    ({ className, ...props }, ref) => {
        return (
            <p ref={ref} className={cn('text-sm leading-relaxed text-muted-foreground', className)} {...props} />
        );
    }
);
DialogDescription.displayName = 'DialogDescription';

export const DialogFooter = forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => {
        return <div ref={ref} className={cn('mt-6 flex justify-end gap-2', className)} {...props} />;
    }
);
DialogFooter.displayName = 'DialogFooter';

export const DialogTrigger = forwardRef<HTMLButtonElement, React.ButtonHTMLAttributes<HTMLButtonElement>>(
    ({ className, ...props }, ref) => {
        return <button ref={ref} className={cn('hidden', className)} {...props} />;
    }
);
DialogTrigger.displayName = 'DialogTrigger';
