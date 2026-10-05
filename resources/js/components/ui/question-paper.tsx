import { Badge } from '@/components/ui/badge';
import { EmptyState } from '@/components/ui/empty-state';
import { cn } from '@/lib/utils';

export type ExamQuestionType = 'multiple_choice' | 'true_false' | 'short_answer';

export type ExamQuestion = {
    number?: number;
    type: ExamQuestionType;
    prompt: string;
    options?: { key: string; text: string }[];
    answer: string | null;
    points: number | null;
};

const TYPE_LABELS: Record<ExamQuestionType, string> = {
    multiple_choice: 'Multiple choice',
    true_false: 'True / False',
    short_answer: 'Short answer',
};

/**
 * Read-only rendering of a question paper. Used on the exam and quiz detail
 * pages for papers imported from Word.
 */
export function QuestionPaper({ questions, className, showAnswers = true }: { questions: ExamQuestion[]; className?: string; showAnswers?: boolean }) {
    if (questions.length === 0) {
        return (
            <EmptyState
                title="No questions yet"
                description="Import a Word question paper to build this automatically."
            />
        );
    }

    return (
        <ol className={cn('space-y-4', className)}>
            {questions.map((question, index) => (
                <li key={index} className="rounded-lg border border-border/70 p-4">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <p className="text-sm font-medium text-foreground">
                            {question.number ?? index + 1}. {question.prompt}
                        </p>
                        <div className="flex shrink-0 items-center gap-2">
                            <Badge variant="secondary">{TYPE_LABELS[question.type] ?? question.type}</Badge>
                            <span className="text-xs text-muted-foreground">{question.points ?? 1} pt</span>
                        </div>
                    </div>

                    {question.options && question.options.length > 0 ? (
                        <ul className="mt-3 grid gap-1.5 sm:grid-cols-2">
                            {question.options.map((option) => (
                                <li
                                    key={option.key}
                                    className={cn(
                                        'rounded-md border px-2 py-1 text-sm',
                                        showAnswers && option.key === question.answer
                                            ? 'border-success/50 bg-success/10 font-medium text-success'
                                            : 'border-border/70 text-muted-foreground'
                                    )}
                                >
                                    <span className="font-mono">{option.key})</span> {option.text}
                                </li>
                            ))}
                        </ul>
                    ) : showAnswers ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            Answer:{' '}
                            <span className="font-medium text-foreground">{question.answer ?? '—'}</span>
                        </p>
                    ) : null}
                </li>
            ))}
        </ol>
    );
}
