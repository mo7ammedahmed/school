import { useId } from 'react';
import { cn } from '@/lib/utils';
import { Label } from '@/components/ui/label';

interface ColorFieldProps {
    /** Label text; also becomes the accessible name of both controls. */
    label: string;
    value: string;
    onChange: (value: string) => void;
    /** Hidden input name so the field survives a plain multipart form post. */
    name?: string;
    hint?: string;
    error?: string;
    className?: string;
}

/**
 * A colour input a human can actually read.
 *
 * A bare `<input type="color">` shows only a swatch: the stored hex value is
 * invisible and cannot be typed or pasted. This pairs the native picker (which
 * carries the accessible name) with a monospace hex field so operators can read
 * and paste brand colours without opening the OS colour dialog.
 */
export function ColorField({ label, value, onChange, name, hint, error, className }: ColorFieldProps) {
    const id = useId();
    const labelId = `${id}-label`;
    const swatchId = `${id}-swatch`;
    const hexId = `${id}-hex`;

    return (
        <div className={cn('w-full', className)}>
            <Label id={labelId} htmlFor={hexId}>
                {label}
            </Label>

            <div className="mt-1.5 flex items-center gap-2">
                <input
                    id={swatchId}
                    type="color"
                    aria-labelledby={labelId}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className={cn(
                        'h-11 w-12 shrink-0 cursor-pointer rounded-lg border bg-transparent p-1 transition-colors',
                        'focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15',
                        error ? 'border-destructive/70' : 'border-input'
                    )}
                />
                <input
                    id={hexId}
                    name={name}
                    type="text"
                    value={value}
                    spellCheck={false}
                    autoComplete="off"
                    maxLength={7}
                    aria-labelledby={labelId}
                    onChange={(event) => onChange(event.target.value)}
                    className={cn(
                        'flex h-11 w-full min-w-0 rounded-lg border bg-card px-3 font-mono text-sm uppercase text-foreground',
                        'transition-[border-color,box-shadow] duration-200',
                        'focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15',
                        error ? 'border-destructive/70' : 'border-input'
                    )}
                />
            </div>

            {error ? (
                <p className="mt-1.5 text-xs text-destructive">{error}</p>
            ) : hint ? (
                <p className="mt-1.5 text-xs text-muted-foreground">{hint}</p>
            ) : null}
        </div>
    );
}
