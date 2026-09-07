import * as React from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
    ({ className, label, error, id, children, ...props }, ref) => {
        const selectId = id || label?.toLowerCase().replace(/\s+/g, '-');

        return (
            <div className="w-full">
                {label && (
                    <label htmlFor={selectId} className="mb-1.5 block text-sm font-medium text-foreground">
                        {label}
                    </label>
                )}
                <div className="relative">
                    <select
                        id={selectId}
                        ref={ref}
                        className={cn(
                            [
                                'flex h-11 w-full appearance-none rounded-lg border border-input bg-card ps-3.5 pe-10 text-[0.9375rem] text-foreground',
                                'shadow-[inset_0_1px_2px_rgba(28,26,22,0.03)] transition-[border-color,box-shadow] duration-200',
                                'focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15',
                                'disabled:cursor-not-allowed disabled:opacity-55',
                            ].join(' '),
                            error && 'border-destructive/70 focus-visible:border-destructive/60 focus-visible:ring-destructive/15',
                            className
                        )}
                        {...props}
                    >
                        {children}
                    </select>
                    <ChevronDown
                        aria-hidden="true"
                        className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                </div>
            </div>
        );
    }
);
Select.displayName = 'Select';
