import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { FormFeedback } from '@/components/ui/form-feedback';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AcademicSettings({ settings }: { settings: { grading_system: string; pass_mark: number; max_score: number } }) {
    const { data, setData, post, processing, errors } = useForm({
        grading_system: settings.grading_system || 'percentage',
        pass_mark: settings.pass_mark || 50,
        max_score: settings.max_score || 100,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/academic');
    };

    return (
        <AppShell
            title="Academic Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Academic' },
            ]}
        >
            <PageHeader
                title="Academic Settings"
                description="Configure academic rules and grading"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/school"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Academic Configuration</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="grading_system">Grading System</Label>
                                <Select
                                    id="grading_system"
                                    value={data.grading_system}
                                    onChange={(e) => setData('grading_system', e.target.value)}
                                    error={errors.grading_system}
                                >
                                    <option value="percentage">Percentage</option>
                                    <option value="gpa">GPA</option>
                                    <option value="letter">Letter Grade</option>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="pass_mark">Pass Mark</Label>
                                <Input id="pass_mark" name="pass_mark" type="number" value={data.pass_mark} onChange={(e) => setData('pass_mark', parseInt(e.target.value))} />
                            </div>
                            <div>
                                <Label htmlFor="max_score">Maximum Score</Label>
                                <Input id="max_score" name="max_score" type="number" value={data.max_score} onChange={(e) => setData('max_score', parseInt(e.target.value))} />
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Settings'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/school">Cancel</Link>
                            </Button>
                            <FormFeedback showErrors={false} />
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
