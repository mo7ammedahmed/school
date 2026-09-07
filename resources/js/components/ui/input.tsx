import { forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    hint?: string;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ className, label, error, hint, id, ...props }, ref) => {
        const inputId = id || label?.toLowerCase().replace(/\s+/g, '-');

        return (
            <div className="w-full">
                {label && (
                    <label htmlFor={inputId} className="mb-1.5 block text-sm font-medium text-foreground">
                        {label}
                    </label>
                )}
                <input
                    id={inputId}
                    ref={ref}
                    className={cn(
                        [
                            'flex h-11 w-full rounded-lg border border-input bg-card px-3.5 text-[0.9375rem] text-foreground',
                            'shadow-[inset_0_1px_2px_rgba(28,26,22,0.03)] transition-[border-color,box-shadow] duration-200',
                            'placeholder:text-muted-foreground/70',
                            'focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15',
                            'disabled:cursor-not-allowed disabled:opacity-55 file:border-0 file:bg-transparent file:text-sm file:font-medium',
                        ].join(' '),
                        error && 'border-destructive/70 focus-visible:border-destructive/60 focus-visible:ring-destructive/15',
                        className
                    )}
                    {...props}
                />
                {hint && !error && <p className="mt-1.5 text-xs text-muted-foreground">{hint}</p>}
                {error && <p className="mt-1.5 text-xs text-destructive">{error}</p>}
            </div>
        );
    }
);

Input.displayName = 'Input';
