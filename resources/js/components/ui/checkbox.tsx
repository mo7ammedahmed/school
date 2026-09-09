import * as React from 'react';
import { cn } from '@/lib/utils';

interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: string;
    error?: string;
}

export const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
    ({ className, label, error, id, ...props }, ref) => {
        const checkboxId = id || label?.toLowerCase().replace(/\s+/g, '-');

        return (
            <div className="flex items-center gap-2">
                <input
                    id={checkboxId}
                    ref={ref}
                    type="checkbox"
                    className={cn(
                        [
                            'size-[18px] shrink-0 cursor-pointer appearance-none rounded-[5px] border border-input bg-card',
                            'shadow-[inset_0_1px_2px_rgba(28,26,22,0.04)] transition-all duration-150',
                            'checked:border-primary checked:bg-primary',
                            'checked:bg-[url("data:image/svg+xml,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20viewBox=%270%200%2016%2016%27%3E%3Cpath%20fill=%27none%27%20stroke=%27white%27%20stroke-linecap=%27round%27%20stroke-linejoin=%27round%27%20stroke-width=%272%27%20d=%27M3.5%208.5l3%203%206-7%27/%3E%3C/svg%3E")] bg-center bg-no-repeat',
                            'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/25 focus-visible:ring-offset-1',
                            'disabled:cursor-not-allowed disabled:opacity-55',
                        ].join(' '),
                        error && 'border-destructive/70',
                        className
                    )}
                    {...props}
                />
                {label && (
                    <label
                        htmlFor={checkboxId}
                        className="select-none text-sm font-semibold leading-none text-foreground/90"
                    >
                        {label}
                    </label>
                )}
            </div>
        );
    }
);
Checkbox.displayName = 'Checkbox';
