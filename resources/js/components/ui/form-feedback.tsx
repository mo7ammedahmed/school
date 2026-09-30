import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface FormFeedbackProps {
    /** Hide the success line where the page renders its own confirmation. */
    showSuccess?: boolean;
    /** Hide the failure block where the shell already reports it. */
    showErrors?: boolean;
    className?: string;
}

/**
 * Shared "did it work?" block for forms.
 *
 * Every one of these screens used to swallow a rejected save: the controller
 * redirected back with validation errors and nothing on the page displayed
 * them, so a form that failed looked identical to one that saved nothing.
 *
 * Inertia shares `flash` and `errors` as page props for both `useForm` posts
 * and plain `<form method="POST">` submissions, so one component covers both.
 *
 * Failure is rendered for the whole app by `AppShell`, so a page that wants its
 * own success confirmation next to the save button uses `showErrors={false}`
 * and the rejected-save case is still reported — once, at the top of the page,
 * on every screen that has a form.
 */
export function FormFeedback({ showSuccess = true, showErrors = true, className }: FormFeedbackProps) {
    const { flash, errors } = usePage<App.PageProps>().props;

    const failure = flash?.error ?? null;
    const messages = Object.entries(errors ?? {});

    return (
        <>
            {showSuccess && flash?.success && (
                <p role="status" className={cn('text-sm text-success', className)}>
                    {flash.success}
                </p>
            )}

            {showErrors && failure && (
                <p role="alert" className={cn('text-sm text-destructive', className)}>
                    {failure}
                </p>
            )}

            {showErrors && messages.length > 0 && (
                <div
                    role="alert"
                    className={cn(
                        'rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive',
                        className,
                    )}
                >
                    <p className="font-medium">Some fields need attention before this can be saved:</p>
                    <ul className="mt-2 list-disc space-y-1 ps-5">
                        {messages.map(([field, message]) => (
                            <li key={field}>
                                {field.replace(/_/g, ' ')}: {message}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </>
    );
}
