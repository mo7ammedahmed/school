import { useState, type ChangeEvent, type FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, AlertTriangle, Download, FileUp, Loader2, ListChecks } from 'lucide-react';

type Option = { id: number; name: string };

type ParsedQuestion = {
    number?: number;
    type: 'multiple_choice' | 'true_false' | 'short_answer';
    prompt: string;
    options?: { key: string; text: string }[];
    answer: string | null;
    points: number | null;
};

type Preview = {
    type: 'exam' | 'quiz';
    offering_id: number;
    name: string;
    description: string | null;
    exam_date: string | null;
    start_time: string | null;
    end_time: string | null;
    room: string | null;
    max_score: number | null;
    time_limit_minutes: number | null;
    questions: ParsedQuestion[];
    warnings: string[];
    source: string;
};

type ImportProps = {
    offerings: Option[];
    preview: Preview | null;
    defaults: { type: 'exam' | 'quiz'; exam_date: string; max_score: number | null; time_limit_minutes: number | null };
};

const TYPE_LABELS: Record<ParsedQuestion['type'], string> = {
    multiple_choice: 'Multiple choice',
    true_false: 'True / False',
    short_answer: 'Short answer',
};

export default function AssessmentImport({ offerings, preview, defaults }: ImportProps) {
    const [confirming, setConfirming] = useState(false);
    const [fileName, setFileName] = useState<string | null>(null);

    const upload = useForm({
        type: defaults.type,
        offering_id: offerings[0]?.id ?? '',
        name: '',
        description: '',
        document: null as File | null,
        exam_date: defaults.exam_date,
        start_time: '',
        end_time: '',
        room: '',
        max_score: '',
        time_limit_minutes: '',
    });

    const onFile = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        upload.setData('document', file);
        setFileName(file?.name ?? null);
    };

    const submitUpload = (event: FormEvent) => {
        event.preventDefault();
        upload.post('/assessments/import/parse', { forceFormData: true, preserveScroll: true });
    };

    const confirm = () => {
        if (!preview) return;
        setConfirming(true);
        router.post(
            '/assessments/import',
            {
                type: preview.type,
                offering_id: preview.offering_id,
                name: preview.name,
                description: preview.description,
                exam_date: preview.exam_date,
                start_time: preview.start_time,
                end_time: preview.end_time,
                room: preview.room,
                max_score: preview.max_score,
                time_limit_minutes: preview.time_limit_minutes,
                questions: preview.questions,
            },
            { preserveScroll: true, onFinish: () => setConfirming(false) }
        );
    };

    const isExam = upload.data.type === 'exam';

    return (
        <AppShell
            title="Import from Word"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Exams', href: '/exams' },
                { label: 'Import from Word' },
            ]}
        >
            <PageHeader
                title="Import an exam or quiz from Word"
                description="Upload a .docx question paper, review what the system read, then save it as an exam or a quiz."
                actions={
                    <Button variant="outline" asChild>
                        <a href="/assessments/import/template">
                            <Download className="me-2 h-4 w-4" />
                            Download template
                        </a>
                    </Button>
                }
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileUp className="h-4 w-4 text-muted-foreground" />
                            Upload document
                        </CardTitle>
                        <CardDescription>Word documents only (.docx), up to 8 MB.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitUpload} className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="type">Create a</Label>
                                <select
                                    id="type"
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={upload.data.type}
                                    onChange={(e) => upload.setData('type', e.target.value as 'exam' | 'quiz')}
                                >
                                    <option value="exam">Exam</option>
                                    <option value="quiz">Quiz</option>
                                </select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="offering_id">Subject &amp; section</Label>
                                <select
                                    id="offering_id"
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={upload.data.offering_id}
                                    onChange={(e) => upload.setData('offering_id', Number(e.target.value))}
                                    required
                                >
                                    <option value="">Select an offering</option>
                                    {offerings.map((offering) => (
                                        <option key={offering.id} value={offering.id}>
                                            {offering.name}
                                        </option>
                                    ))}
                                </select>
                                {upload.errors.offering_id && (
                                    <p className="text-sm text-destructive">{upload.errors.offering_id}</p>
                                )}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="name">Title (optional)</Label>
                                <Input
                                    id="name"
                                    value={upload.data.name}
                                    onChange={(e) => upload.setData('name', e.target.value)}
                                    placeholder="Uses the “Title:” line in the document when left blank"
                                />
                            </div>

                            {isExam ? (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="exam_date">Exam date</Label>
                                        <Input
                                            id="exam_date"
                                            type="date"
                                            value={upload.data.exam_date}
                                            onChange={(e) => upload.setData('exam_date', e.target.value)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="room">Room (optional)</Label>
                                        <Input
                                            id="room"
                                            value={upload.data.room}
                                            onChange={(e) => upload.setData('room', e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="start_time">Start time</Label>
                                        <Input
                                            id="start_time"
                                            type="time"
                                            value={upload.data.start_time}
                                            onChange={(e) => upload.setData('start_time', e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="end_time">End time</Label>
                                        <Input
                                            id="end_time"
                                            type="time"
                                            value={upload.data.end_time}
                                            onChange={(e) => upload.setData('end_time', e.target.value)}
                                        />
                                        {upload.errors.end_time && (
                                            <p className="text-sm text-destructive">{upload.errors.end_time}</p>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="time_limit_minutes">Time limit (minutes, optional)</Label>
                                    <Input
                                        id="time_limit_minutes"
                                        type="number"
                                        min={1}
                                        value={upload.data.time_limit_minutes}
                                        onChange={(e) => upload.setData('time_limit_minutes', e.target.value)}
                                    />
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="max_score">Total marks (optional)</Label>
                                <Input
                                    id="max_score"
                                    type="number"
                                    min={0}
                                    step="0.5"
                                    value={upload.data.max_score}
                                    onChange={(e) => upload.setData('max_score', e.target.value)}
                                    placeholder="Sum of question points"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="document">Word document</Label>
                                <Input
                                    id="document"
                                    type="file"
                                    accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    onChange={onFile}
                                    required
                                />
                                {fileName && <p className="text-xs text-muted-foreground">{fileName}</p>}
                                {upload.errors.document && (
                                    <p className="text-sm text-destructive">{upload.errors.document}</p>
                                )}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="description">Description (optional)</Label>
                                <Textarea
                                    id="description"
                                    rows={2}
                                    value={upload.data.description}
                                    onChange={(e) => upload.setData('description', e.target.value)}
                                />
                            </div>

                            <div className="flex flex-wrap gap-3 md:col-span-2">
                                <Button type="submit" disabled={upload.processing}>
                                    {upload.processing ? (
                                        <>
                                            <Loader2 className="me-2 h-4 w-4 animate-spin" />
                                            Reading document...
                                        </>
                                    ) : (
                                        <>
                                            <FileUp className="me-2 h-4 w-4" />
                                            Read document
                                        </>
                                    )}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <a href="/assessments/import/template">Get the template</a>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Document format</CardTitle>
                        <CardDescription>Number every question and mark the correct answer.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <pre className="overflow-x-auto rounded-lg border border-border bg-muted/50 p-3 font-mono text-xs leading-relaxed text-foreground">
{`Title: Chapter 4 Quiz

1. What is 2 + 2?
A) 3
B) 4
Answer: B
Points: 2

2. Water boils at 100°C.
Answer: True

3. Name the gas plants absorb.
Answer: Carbon dioxide`}
                        </pre>
                        <ul className="space-y-1.5 text-muted-foreground">
                            <li>
                                <strong className="text-foreground">1.</strong> or <strong className="text-foreground">Q:</strong> starts a
                                question.
                            </li>
                            <li>
                                <strong className="text-foreground">A)</strong> lines become the choices.
                            </li>
                            <li>
                                <strong className="text-foreground">Answer:</strong> takes a letter, True/False, or text.
                            </li>
                            <li>
                                No options plus <em>True</em>/<em>False</em> becomes a true/false question.
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            {preview && (
                <Card className="mt-6">
                    <CardHeader className="gap-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <ListChecks className="h-4 w-4 text-muted-foreground" />
                                    Review: {preview.name}
                                </CardTitle>
                                <CardDescription>
                                    Read from {preview.source} · {preview.questions.length} question
                                    {preview.questions.length === 1 ? '' : 's'} ·{' '}
                                    {preview.type === 'exam' ? 'Exam' : 'Quiz'}
                                </CardDescription>
                            </div>
                            <Button onClick={confirm} disabled={confirming || preview.questions.length === 0}>
                                {confirming ? (
                                    <>
                                        <Loader2 className="me-2 h-4 w-4 animate-spin" />
                                        Saving...
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle2 className="me-2 h-4 w-4" />
                                        Create {preview.type === 'exam' ? 'exam' : 'quiz'}
                                    </>
                                )}
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-5">
                        {preview.warnings.length > 0 && (
                            <div className="space-y-1.5 rounded-lg border border-warning/40 bg-warning/10 p-3">
                                {preview.warnings.map((warning) => (
                                    <p key={warning} className="flex items-start gap-2 text-sm text-warning">
                                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                                        {warning}
                                    </p>
                                ))}
                            </div>
                        )}

                        <ol className="space-y-4">
                            {preview.questions.map((question, index) => (
                                <li key={index} className="rounded-lg border border-border/70 p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <p className="text-sm font-medium text-foreground">
                                            {question.number ?? index + 1}. {question.prompt}
                                        </p>
                                        <div className="flex shrink-0 items-center gap-2">
                                            <Badge variant="secondary">{TYPE_LABELS[question.type]}</Badge>
                                            <span className="text-xs text-muted-foreground">
                                                {question.points ?? 1} pt
                                            </span>
                                        </div>
                                    </div>

                                    {question.options && question.options.length > 0 && (
                                        <ul className="mt-3 grid gap-1.5 sm:grid-cols-2">
                                            {question.options.map((option) => (
                                                <li
                                                    key={option.key}
                                                    className={
                                                        'rounded-md border px-2 py-1 text-sm ' +
                                                        (option.key === question.answer
                                                            ? 'border-success/50 bg-success/10 font-medium text-success'
                                                            : 'border-border/70 text-muted-foreground')
                                                    }
                                                >
                                                    <span className="font-mono">{option.key})</span> {option.text}
                                            </li>
                                            ))}
                                        </ul>
                                    )}

                                    {(!question.options || question.options.length === 0) && (
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            Answer:{' '}
                                            <span className="font-medium text-foreground">
                                                {question.answer ?? 'not detected'}
                                            </span>
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </CardContent>
                </Card>
            )}
        </AppShell>
    );
}
