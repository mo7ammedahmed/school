import { useId, useState, type ChangeEvent, type InputHTMLAttributes } from 'react';
import { Upload } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import { cn } from '@/lib/utils';

type FileInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> & {
    className?: string;
    /** Overrides the button's own wording, for a control that says more. */
    buttonLabel?: string;
    hint?: string;
    error?: string | null;
};

/**
 * A file control that speaks the page's language.
 *
 * A plain `<input type="file">` paints "Choose File" and "No file chosen"
 * inside itself, in the *browser's* language — so an Arabic screen shows English
 * words that no translation can reach, because they are not in the document at
 * all. The native control is kept for its behaviour (and for the keyboard) but
 * hidden, and its button and caption are drawn here instead, from the same
 * dictionary as the rest of the interface. The chosen file is named underneath,
 * which the native caption only ever did in the browser's own words.
 */
export function FileInput({
    className,
    buttonLabel,
    hint,
    error,
    id,
    onChange,
    ...props
}: FileInputProps) {
    const { locale } = useLocale();
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const [fileName, setFileName] = useState('');

    const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
        setFileName(event.target.files?.[0]?.name ?? '');
        onChange?.(event);
    };

    return (
        <div className={cn('w-full space-y-1.5', className)}>
            <input
                {...props}
                id={inputId}
                type="file"
                onChange={handleChange}
                className="peer sr-only"
            />

            <div className="flex flex-wrap items-center gap-2">
                <Label
                    htmlFor={inputId}
                    className={cn(
                        'inline-flex w-fit cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground',
                        'transition-colors hover:bg-primary/90',
                        'peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/30',
                        'peer-disabled:cursor-not-allowed peer-disabled:opacity-55',
                    )}
                >
                    <Upload className="size-4" aria-hidden="true" />
                    {buttonLabel ?? t(locale, 'common.chooseFile')}
                </Label>

                <span className="truncate text-xs text-muted-foreground">
                    {fileName || t(locale, 'common.noFileChosen')}
                </span>
            </div>

            {hint && !error && <p className="text-xs text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
