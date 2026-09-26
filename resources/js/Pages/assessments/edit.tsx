import { type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

type Option = { id: number; label: string };
type Category = { id: number; name: string };

type AssessmentsEditProps = {
    assessment: {
        id: number;
        name: string;
        description: string | null;
        offering_id: number;
        grading_category_id: number;
        semester_id: number | null;
        due_date: string | null;
        max_score: number | null;
        weight: number | null;
        is_published: boolean;
    };
    offerings: Option[];
    gradingCategories: Category[];
};

export default function AssessmentsEdit({ assessment, offerings, gradingCategories }: AssessmentsEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        offering_id: String(assessment.offering_id),
        grading_category_id: String(assessment.grading_category_id),
        semester_id: assessment.semester_id ? String(assessment.semester_id) : '',
        name: assessment.name,
        description: assessment.description ?? '',
        due_date: assessment.due_date ?? '',
        max_score: assessment.max_score === null ? '' : String(assessment.max_score),
        weight: assessment.weight === null ? '100' : String(assessment.weight),
        is_published: assessment.is_published,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(`/assessments/${assessment.id}`);
    };

    return (
        <AppShell
            title="Edit Assessment"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Assessments', href: '/assessments' },
                { label: assessment.name },
            ]}
        >
            <PageHeader
                title="Edit Assessment"
                description={assessment.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/assessments">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Assessment Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Assessment Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="offering_id">Offering</Label>
                                <select
                                    id="offering_id"
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={data.offering_id}
                                    onChange={(e) => setData('offering_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select offering</option>
                                    {offerings.map((offering) => (
                                        <option key={offering.id} value={offering.id}>
                                            {offering.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.offering_id && <p className="text-sm text-destructive">{errors.offering_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="grading_category_id">Grading Category</Label>
                                <select
                                    id="grading_category_id"
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={data.grading_category_id}
                                    onChange={(e) => setData('grading_category_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select category</option>
                                    {gradingCategories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.grading_category_id && (
                                    <p className="text-sm text-destructive">{errors.grading_category_id}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="due_date">Due Date</Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    value={data.due_date}
                                    onChange={(e) => setData('due_date', e.target.value)}
                                />
                                {errors.due_date && <p className="text-sm text-destructive">{errors.due_date}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="max_score">Max Score</Label>
                                <Input
                                    id="max_score"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.max_score}
                                    onChange={(e) => setData('max_score', e.target.value)}
                                />
                                {errors.max_score && <p className="text-sm text-destructive">{errors.max_score}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="weight">Weight (%)</Label>
                                <Input
                                    id="weight"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={data.weight}
                                    onChange={(e) => setData('weight', e.target.value)}
                                    required
                                />
                                {errors.weight && <p className="text-sm text-destructive">{errors.weight}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    className="min-h-[100px] w-full rounded-md border border-input bg-background p-3 text-sm"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-2 md:col-span-2">
                                <input
                                    id="is_published"
                                    type="checkbox"
                                    checked={data.is_published}
                                    onChange={(e) => setData('is_published', e.target.checked)}
                                />
                                <Label htmlFor="is_published">Publish to students</Label>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/assessments">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Update Assessment'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
