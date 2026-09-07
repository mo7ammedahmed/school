import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AttendanceSettings({ settings }: { settings: { late_threshold_minutes: number; excused_types: string[] } }) {
    const { data, setData, post, processing } = useForm({
        late_threshold_minutes: settings.late_threshold_minutes || 15,
        excused_types: settings.excused_types?.join(',') || 'sick,excused',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/attendance');
    };

    return (
        <AppShell
            title="Attendance Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Attendance' },
            ]}
        >
            <PageHeader
                title="Attendance Settings"
                description="Configure attendance rules and thresholds"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/general"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Attendance Configuration</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="late_threshold_minutes">Late Threshold (minutes)</Label>
                                <Input id="late_threshold_minutes" name="late_threshold_minutes" type="number" value={data.late_threshold_minutes} onChange={(e) => setData('late_threshold_minutes', parseInt(e.target.value))} />
                            </div>
                            <div>
                                <Label htmlFor="excused_types">Excused Types (comma-separated)</Label>
                                <Input id="excused_types" name="excused_types" value={data.excused_types} onChange={(e) => setData('excused_types', e.target.value)} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/general">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Settings'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
