// Facts for this file:
// 1. Called by: routes/web.php (GET/POST /settings/grading plus the
//    scales/categories routes registered alongside it).
// 2. Data flow: reads school, gradingScales, gradingCategories and settings;
//    posts the rounding/extracurricular form, and posts/puts/deletes one scale
//    or category at a time through router().
// 3. Every button here used to be decorative (no handlers, no routes) and the
//    settings form POSTed to a controller method that did not exist, so the
//    screen answered 500 on save. The list markup also mixed the action
//    buttons and the grade chips into one row, which collided at tablet
//    widths, and used hardcoded Tailwind colours that ignored dark mode.

import { useState, type FormEvent } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormFeedback } from '@/components/ui/form-feedback';
import { ArrowLeft, Edit, Trash2, Plus, X } from 'lucide-react';

interface GradingScale {
    id: number;
    name: string;
    description: string | null;
    scale: Array<{ grade: string; min: number; max: number }> | null;
    is_default: boolean;
}

interface GradingCategory {
    id: number;
    name: string;
    code: string | null;
    weight: number;
    description: string | null;
}

interface Props {
    school: { id: number; name: string };
    gradingScales: GradingScale[];
    gradingCategories: GradingCategory[];
    settings: {
        rounding_method: string;
        include_extracurricular: boolean;
    };
}

type GradeRow = { grade: string; min: number | string; max: number | string };

type ScaleDraft = {
    id: number | null;
    name: string;
    description: string;
    is_default: boolean;
    scale: GradeRow[];
};

type CategoryDraft = {
    id: number | null;
    name: string;
    code: string;
    weight: number | string;
    description: string;
};

function blankScale(): ScaleDraft {
    return {
        id: null,
        name: '',
        description: '',
        is_default: false,
        scale: [
            { grade: 'A', min: 90, max: 100 },
            { grade: 'B', min: 80, max: 89 },
        ],
    };
}

function draftFromScale(scale: GradingScale): ScaleDraft {
    return {
        id: scale.id,
        name: scale.name,
        description: scale.description ?? '',
        is_default: scale.is_default,
        scale: (scale.scale ?? []).map((row) => ({ grade: row.grade, min: row.min, max: row.max })),
    };
}

function blankCategory(): CategoryDraft {
    return { id: null, name: '', code: '', weight: 20, description: '' };
}

function draftFromCategory(category: GradingCategory): CategoryDraft {
    return {
        id: category.id,
        name: category.name,
        code: category.code ?? '',
        weight: category.weight,
        description: category.description ?? '',
    };
}

export default function GradingSettings({ school, gradingScales, gradingCategories, settings }: Props) {

    const [scaleDraft, setScaleDraft] = useState<ScaleDraft | null>(null);
    const [categoryDraft, setCategoryDraft] = useState<CategoryDraft | null>(null);

    const calculator = useForm({
        rounding_method: settings.rounding_method,
        include_extracurricular: settings.include_extracurricular,
    });

    const submitCalculator = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        calculator.post('/settings/grading', { preserveScroll: true });
    };

    const saveScale = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (scaleDraft === null) return;

        const payload = {
            name: scaleDraft.name,
            description: scaleDraft.description === '' ? null : scaleDraft.description,
            is_default: scaleDraft.is_default,
            scale: scaleDraft.scale.map((row) => ({
                grade: row.grade,
                min: Number(row.min),
                max: Number(row.max),
            })),
        };

        const options = { preserveScroll: true, onSuccess: () => setScaleDraft(null) };

        if (scaleDraft.id === null) {
            router.post('/settings/grading/scales', payload, options);
        } else {
            router.put(`/settings/grading/scales/${scaleDraft.id}`, payload, options);
        }
    };

    const saveCategory = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (categoryDraft === null) return;

        const payload = {
            name: categoryDraft.name,
            code: categoryDraft.code === '' ? null : categoryDraft.code,
            weight: Number(categoryDraft.weight),
            description: categoryDraft.description === '' ? null : categoryDraft.description,
        };

        const options = { preserveScroll: true, onSuccess: () => setCategoryDraft(null) };

        if (categoryDraft.id === null) {
            router.post('/settings/grading/categories', payload, options);
        } else {
            router.put(`/settings/grading/categories/${categoryDraft.id}`, payload, options);
        }
    };

    const deleteScale = (scale: GradingScale) => {
        if (!window.confirm(`Delete the scale “${scale.name}”? Grades already recorded keep their marks.`)) return;

        router.delete(`/settings/grading/scales/${scale.id}`, { preserveScroll: true });
    };

    const deleteCategory = (category: GradingCategory) => {
        if (!window.confirm(`Delete the category “${category.name}”?`)) return;

        router.delete(`/settings/grading/categories/${category.id}`, { preserveScroll: true });
    };

    const makeDefault = (scale: GradingScale) => {
        router.put(
            `/settings/grading/scales/${scale.id}`,
            {
                name: scale.name,
                description: scale.description,
                is_default: true,
                scale: (scale.scale ?? []).map((row) => ({ grade: row.grade, min: row.min, max: row.max })),
            },
            { preserveScroll: true },
        );
    };

    return (
        <AppShell
            title="Grading Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Grading' },
            ]}
        >
            <PageHeader
                title="Grading settings"
                description={`Grade scales and weighting for ${school.name}.`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/school">
                            <ArrowLeft className="me-2 h-4 w-4" aria-hidden="true" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <div className="mt-4">
                <FormFeedback />
            </div>

            <div className="mt-4 space-y-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-2">
                            <CardTitle>Grading scales</CardTitle>
                            <CardDescription>
                                Convert numerical scores into letter grades or grade points. Each scale is a list of
                                grade levels with minimum and maximum percentages.
                            </CardDescription>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setScaleDraft(blankScale())}
                            className="shrink-0"
                        >
                            <Plus className="me-2 h-4 w-4" aria-hidden="true" /> New scale
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {scaleDraft && (
                            <form
                                onSubmit={saveScale}
                                className="space-y-5 rounded-lg border border-border/70 bg-muted/30 p-4"
                                aria-label={scaleDraft.id === null ? 'New grading scale' : 'Edit grading scale'}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h3 className="text-sm font-semibold text-foreground">
                                        {scaleDraft.id === null ? 'New grading scale' : `Editing “${scaleDraft.name}”`}
                                    </h3>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Close scale editor"
                                        onClick={() => setScaleDraft(null)}
                                    >
                                        <X className="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label htmlFor="scale_name">Scale name</Label>
                                        <Input
                                            id="scale_name"
                                            value={scaleDraft.name}
                                            onChange={(event) =>
                                                setScaleDraft({ ...scaleDraft, name: event.target.value })
                                            }
                                            placeholder="Secondary — percentage"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="scale_description">Description</Label>
                                        <Input
                                            id="scale_description"
                                            value={scaleDraft.description}
                                            onChange={(event) =>
                                                setScaleDraft({ ...scaleDraft, description: event.target.value })
                                            }
                                        />
                                    </div>
                                </div>

                                <fieldset className="space-y-3">
                                    <legend className="text-sm font-semibold text-foreground">Grade levels</legend>
                                    {scaleDraft.scale.map((row, index) => (
                                        <div
                                            key={index}
                                            className="grid grid-cols-[1fr_5rem_5rem_auto] items-end gap-3"
                                        >
                                            <div>
                                                <Label htmlFor={`scale_grade_${index}`}>Grade</Label>
                                                <Input
                                                    id={`scale_grade_${index}`}
                                                    value={row.grade}
                                                    onChange={(event) => {
                                                        const next = [...scaleDraft.scale];
                                                        next[index] = { ...row, grade: event.target.value };
                                                        setScaleDraft({ ...scaleDraft, scale: next });
                                                    }}
                                                    placeholder="A"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <Label htmlFor={`scale_min_${index}`}>Min %</Label>
                                                <Input
                                                    id={`scale_min_${index}`}
                                                    type="number"
                                                    min={0}
                                                    max={100}
                                                    step="0.01"
                                                    value={row.min}
                                                    onChange={(event) => {
                                                        const next = [...scaleDraft.scale];
                                                        next[index] = { ...row, min: event.target.value };
                                                        setScaleDraft({ ...scaleDraft, scale: next });
                                                    }}
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <Label htmlFor={`scale_max_${index}`}>Max %</Label>
                                                <Input
                                                    id={`scale_max_${index}`}
                                                    type="number"
                                                    min={0}
                                                    max={100}
                                                    step="0.01"
                                                    value={row.max}
                                                    onChange={(event) => {
                                                        const next = [...scaleDraft.scale];
                                                        next[index] = { ...row, max: event.target.value };
                                                        setScaleDraft({ ...scaleDraft, scale: next });
                                                    }}
                                                    required
                                                />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                aria-label={`Remove grade level ${index + 1}`}
                                                disabled={scaleDraft.scale.length <= 1}
                                                onClick={() =>
                                                    setScaleDraft({
                                                        ...scaleDraft,
                                                        scale: scaleDraft.scale.filter((_, i) => i !== index),
                                                    })
                                                }
                                            >
                                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            </Button>
                                        </div>
                                    ))}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setScaleDraft({
                                                ...scaleDraft,
                                                scale: [...scaleDraft.scale, { grade: '', min: 0, max: 0 }],
                                            })
                                        }
                                    >
                                        <Plus className="me-2 h-4 w-4" aria-hidden="true" /> Add grade level
                                    </Button>
                                </fieldset>

                                <Checkbox
                                    id="is_default_scale"
                                    label="Use this scale by default"
                                    checked={scaleDraft.is_default}
                                    onChange={(event) =>
                                        setScaleDraft({ ...scaleDraft, is_default: event.target.checked })
                                    }
                                />

                                <FormFeedback showSuccess={false} />

                                <div className="flex flex-wrap gap-3">
                                    <Button type="submit">
                                        {scaleDraft.id === null ? 'Create scale' : 'Save scale'}
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => setScaleDraft(null)}>
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        )}

                        {gradingScales.length > 0 ? (
                            gradingScales.map((scale) => (
                                <div
                                    key={scale.id}
                                    className="rounded-lg border border-border/70 border-s-4 border-s-primary bg-card p-4"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0 space-y-1">
                                            <h3 className="flex flex-wrap items-center gap-2 text-sm font-semibold text-foreground">
                                                {scale.is_default && <Badge>Default</Badge>}
                                                <span>{scale.name}</span>
                                            </h3>
                                            {scale.description && (
                                                <p className="text-sm text-muted-foreground">{scale.description}</p>
                                            )}
                                        </div>
                                        <div className="flex shrink-0 flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setScaleDraft(draftFromScale(scale))}
                                            >
                                                <Edit className="me-2 h-4 w-4" aria-hidden="true" /> Edit
                                            </Button>
                                            {!scale.is_default && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => makeDefault(scale)}
                                                >
                                                    Set as default
                                                </Button>
                                            )}
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => deleteScale(scale)}
                                            >
                                                <Trash2 className="me-2 h-4 w-4" aria-hidden="true" /> Delete
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {(scale.scale ?? []).map((grade, index) => (
                                            <span
                                                key={`${grade.grade}-${index}`}
                                                className="rounded-md bg-muted px-2 py-1 text-xs text-muted-foreground"
                                            >
                                                {grade.grade}: {grade.min}% – {grade.max}%
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No grading scales yet. Use “New scale” to add one.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-2">
                            <CardTitle>Grading categories</CardTitle>
                            <CardDescription>
                                How assessment types contribute to the final grade. Each category has a weight.
                            </CardDescription>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setCategoryDraft(blankCategory())}
                            className="shrink-0"
                        >
                            <Plus className="me-2 h-4 w-4" aria-hidden="true" /> New category
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {categoryDraft && (
                            <form
                                onSubmit={saveCategory}
                                className="space-y-5 rounded-lg border border-border/70 bg-muted/30 p-4"
                                aria-label={categoryDraft.id === null ? 'New grading category' : 'Edit grading category'}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h3 className="text-sm font-semibold text-foreground">
                                        {categoryDraft.id === null
                                            ? 'New grading category'
                                            : `Editing “${categoryDraft.name}”`}
                                    </h3>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Close category editor"
                                        onClick={() => setCategoryDraft(null)}
                                    >
                                        <X className="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div>
                                        <Label htmlFor="category_name">Name</Label>
                                        <Input
                                            id="category_name"
                                            value={categoryDraft.name}
                                            onChange={(event) =>
                                                setCategoryDraft({ ...categoryDraft, name: event.target.value })
                                            }
                                            placeholder="Coursework"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="category_code">Code</Label>
                                        <Input
                                            id="category_code"
                                            value={categoryDraft.code}
                                            onChange={(event) =>
                                                setCategoryDraft({ ...categoryDraft, code: event.target.value })
                                            }
                                            placeholder="CW"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="category_weight">Weight (%)</Label>
                                        <Input
                                            id="category_weight"
                                            type="number"
                                            min={0.01}
                                            max={100}
                                            step="0.01"
                                            value={categoryDraft.weight}
                                            onChange={(event) =>
                                                setCategoryDraft({ ...categoryDraft, weight: event.target.value })
                                            }
                                            required
                                        />
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="category_description">Description</Label>
                                    <Input
                                        id="category_description"
                                        value={categoryDraft.description}
                                        onChange={(event) =>
                                            setCategoryDraft({ ...categoryDraft, description: event.target.value })
                                        }
                                    />
                                </div>

                                <FormFeedback showSuccess={false} />

                                <div className="flex flex-wrap gap-3">
                                    <Button type="submit">
                                        {categoryDraft.id === null ? 'Create category' : 'Save category'}
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => setCategoryDraft(null)}>
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        )}

                        {gradingCategories.length > 0 ? (
                            gradingCategories.map((category) => (
                                <div
                                    key={category.id}
                                    className="rounded-lg border border-border/70 border-s-4 border-s-primary bg-card p-4"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0 space-y-1">
                                            <h3 className="flex flex-wrap items-center gap-2 text-sm font-semibold text-foreground">
                                                <span>{category.name}</span>
                                                {category.code && <Badge variant="secondary">{category.code}</Badge>}
                                            </h3>
                                            {category.description && (
                                                <p className="text-sm text-muted-foreground">{category.description}</p>
                                            )}
                                            <p className="text-xs text-muted-foreground">
                                                Weight: {category.weight}%
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCategoryDraft(draftFromCategory(category))}
                                            >
                                                <Edit className="me-2 h-4 w-4" aria-hidden="true" /> Edit
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => deleteCategory(category)}
                                            >
                                                <Trash2 className="me-2 h-4 w-4" aria-hidden="true" /> Delete
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No grading categories yet. Use “New category” to add one.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Calculation</CardTitle>
                        <CardDescription>How marks are rounded and what counts towards the final grade.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitCalculator} className="space-y-5">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="rounding_method">Rounding method</Label>
                                    <Select
                                        id="rounding_method"
                                        value={calculator.data.rounding_method}
                                        onChange={(event) =>
                                            calculator.setData('rounding_method', event.target.value)
                                        }
                                    >
                                        <option value="nearest">Nearest (standard rounding)</option>
                                        <option value="floor">Round down</option>
                                        <option value="ceil">Round up</option>
                                    </Select>
                                    {calculator.errors.rounding_method && (
                                        <p className="mt-1.5 text-xs text-destructive">
                                            {calculator.errors.rounding_method}
                                        </p>
                                    )}
                                    <p className="mt-1.5 text-xs text-muted-foreground">
                                        Applied when converting between numerical and letter grades.
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Checkbox
                                        id="include_extracurricular"
                                        label="Include extracurricular activities in the final grade"
                                        checked={calculator.data.include_extracurricular}
                                        onChange={(event) =>
                                            calculator.setData('include_extracurricular', event.target.checked)
                                        }
                                    />
                                    {calculator.errors.include_extracurricular && (
                                        <p className="text-xs text-destructive">
                                            {calculator.errors.include_extracurricular}
                                        </p>
                                    )}
                                    <p className="text-xs text-muted-foreground">
                                        When enabled, extracurricular scores are folded into the final grade.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                                <Button type="submit" disabled={calculator.processing}>
                                    {calculator.processing ? 'Saving…' : 'Save calculation settings'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={calculator.processing}
                                    onClick={() => calculator.reset()}
                                >
                                    Reset changes
                                </Button>
                                <FormFeedback showSuccess={false} />
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
