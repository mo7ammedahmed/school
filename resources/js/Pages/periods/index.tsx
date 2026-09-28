import { useState, type FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { EmptyState } from '@/components/ui/empty-state';
import { Trash2 } from 'lucide-react';
import { type ColumnDef } from '@/lib/table';

type PeriodRow = {
    id: number;
    name: string;
    name_en: string;
    name_ar: string | null;
    code: string | null;
    start_time: string;
    end_time: string;
    sort_order: number;
    is_break: boolean;
};

export default function PeriodsIndex({ periods }: { periods: PeriodRow[] }) {
    const [confirmingDelete, setConfirmingDelete] = useState<number | null>(null);

    const form = useForm({
        name_en: '',
        name_ar: '',
        code: '',
        start_time: '08:00',
        end_time: '08:45',
        sort_order: '0',
        is_break: false as boolean,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/periods', {
            preserveScroll: true,
            onSuccess: () => form.reset('name_en', 'name_ar', 'code'),
        });
    };

    const columns: ColumnDef<PeriodRow>[] = [
        {
            accessorKey: 'sort_order',
            header: 'Order',
        },
        {
            accessorKey: 'name',
            header: 'Period',
            cell: ({ row }) => (
                <span className="inline-flex items-center gap-2">
                    {row.original.name}
                    {row.original.is_break && <Badge variant="secondary">Break</Badge>}
                </span>
            ),
        },
        {
            accessorKey: 'code',
            header: 'Code',
            cell: ({ row }) => row.original.code ?? '—',
        },
        {
            accessorKey: 'start_time',
            header: 'Start',
        },
        {
            accessorKey: 'end_time',
            header: 'End',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <Button
                    variant="ghost"
                    size="sm"
                    aria-label={`Delete ${row.original.name}`}
                    disabled={confirmingDelete === row.original.id}
                    onClick={() => {
                        if (confirmingDelete === row.original.id) {
                            router.delete(`/periods/${row.original.id}`, {
                                preserveScroll: true,
                                onFinish: () => setConfirmingDelete(null),
                            });
                            return;
                        }
                        setConfirmingDelete(row.original.id);
                    }}
                >
                    <Trash2 className="h-4 w-4" />
                    {confirmingDelete === row.original.id ? 'Confirm' : ''}
                </Button>
            ),
        },
    ];

    return (
        <AppShell
            title="Periods"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Timetable', href: '/timetable/grid' },
                { label: 'Periods' },
            ]}
        >
            <PageHeader
                title="Periods"
                description="The bell schedule that rows in the timetable grid line up with"
            />

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Add a period</CardTitle>
                    <CardDescription>Periods apply to the whole school.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4 md:grid-cols-4">
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="name_en">Name (English)</Label>
                            <Input
                                id="name_en"
                                value={form.data.name_en}
                                onChange={(e) => form.setData('name_en', e.target.value)}
                            />
                            {form.errors.name_en && <p className="text-sm text-destructive">{form.errors.name_en}</p>}
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="name_ar">Name (Arabic)</Label>
                            <Input
                                id="name_ar"
                                dir="rtl"
                                value={form.data.name_ar}
                                onChange={(e) => form.setData('name_ar', e.target.value)}
                            />
                            <TranslatePair enId="name_en" arId="name_ar" />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="code">Code</Label>
                            <Input
                                id="code"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="start_time">Start</Label>
                            <Input
                                id="start_time"
                                type="time"
                                value={form.data.start_time}
                                onChange={(e) => form.setData('start_time', e.target.value)}
                                required
                            />
                            {form.errors.start_time && (
                                <p className="text-sm text-destructive">{form.errors.start_time}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="end_time">End</Label>
                            <Input
                                id="end_time"
                                type="time"
                                value={form.data.end_time}
                                onChange={(e) => form.setData('end_time', e.target.value)}
                                required
                            />
                            {form.errors.end_time && <p className="text-sm text-destructive">{form.errors.end_time}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="sort_order">Order</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min="0"
                                value={form.data.sort_order}
                                onChange={(e) => form.setData('sort_order', e.target.value)}
                            />
                        </div>

                        <div className="flex items-center gap-2 md:col-span-2">
                            <input
                                id="is_break"
                                type="checkbox"
                                checked={form.data.is_break}
                                onChange={(e) => form.setData('is_break', e.target.checked)}
                            />
                            <Label htmlFor="is_break">This is a break</Label>
                        </div>

                        <div className="md:col-span-4">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving...' : 'Add period'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div className="mt-6">
                {periods.length === 0 ? (
                    <EmptyState
                        title="No periods yet"
                        description="Add the first period to build a bell schedule for the timetable grid."
                    />
                ) : (
                    <DataTable columns={columns} data={periods} emptyMessage="No periods yet." />
                )}
            </div>
        </AppShell>
    );
}
