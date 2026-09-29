import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';

export type AnnouncementDraft = {
    id: number;
    title: string | null;
    title_ar: string | null;
    body: string | null;
    body_ar: string | null;
    target_audience: string | null;
    start_date: string | null;
    end_date: string | null;
    is_published: boolean;
};

export const AUDIENCES = [
    { value: 'all', label: 'All' },
    { value: 'students', label: 'Students' },
    { value: 'teachers', label: 'Teachers' },
    { value: 'parents', label: 'Parents' },
    { value: 'staff', label: 'Staff' },
];

/**
 * The announcement form, in one place.
 *
 * Create and edit were two ~90-line copies of the same fields, so a change to
 * the bilingual pair — the translate button, the hint text, a new input — had
 * to be made twice and could silently disagree between the two screens. The
 * pages keep what actually differs: the URL, the verb and the button words.
 *
 * On the create screen the translate button writes into the field; on edit it
 * also persists, because a record that already exists can be translated without
 * making the operator press save.
 */
export function AnnouncementForm({
    announcement,
    action,
    method = 'POST',
    submitLabel,
}: {
    announcement?: AnnouncementDraft;
    action: string;
    method?: 'POST' | 'PUT';
    submitLabel: string;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Announcement Information</CardTitle>
            </CardHeader>
            <CardContent>
                <form className="space-y-6" method="POST" action={action}>
                    {method === 'PUT' && <input type="hidden" name="_method" value="PUT" />}
                    <div className="grid gap-6 md:grid-cols-2">
                        <div>
                            <Label htmlFor="title">Title (English)</Label>
                            <Input id="title" name="title" defaultValue={announcement?.title ?? ''} />
                        </div>
                        <div>
                            <Label htmlFor="title_ar">Title (Arabic)</Label>
                            <Input id="title_ar" name="title_ar" dir="rtl" defaultValue={announcement?.title_ar ?? ''} />
                        </div>
                        <div className="md:col-span-2">
                            <TranslatePair enId="title" arId="title_ar" persist={persisted(announcement, 'title', 'title_ar')} />
                        </div>
                        <div>
                            <Label htmlFor="target_audience">Target Audience</Label>
                            <select
                                id="target_audience"
                                name="target_audience"
                                className="input"
                                required
                                defaultValue={announcement?.target_audience ?? 'all'}
                            >
                                {AUDIENCES.map((audience) => (
                                    <option key={audience.value} value={audience.value}>
                                        {audience.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="is_published">Published</Label>
                            <select
                                id="is_published"
                                name="is_published"
                                className="input"
                                required
                                defaultValue={announcement?.is_published ? '1' : '0'}
                            >
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="start_date">Start Date</Label>
                            <Input
                                id="start_date"
                                name="start_date"
                                type="date"
                                required
                                defaultValue={announcement?.start_date?.slice(0, 10) ?? ''}
                            />
                        </div>
                        <div>
                            <Label htmlFor="end_date">End Date</Label>
                            <Input
                                id="end_date"
                                name="end_date"
                                type="date"
                                required
                                defaultValue={announcement?.end_date?.slice(0, 10) ?? ''}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Label htmlFor="body">Body (English)</Label>
                            <textarea
                                id="body"
                                name="body"
                                className="input min-h-[200px]"
                                defaultValue={announcement?.body ?? ''}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Label htmlFor="body_ar">Body (Arabic)</Label>
                            <textarea
                                id="body_ar"
                                name="body_ar"
                                dir="rtl"
                                className="input min-h-[200px]"
                                defaultValue={announcement?.body_ar ?? ''}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <TranslatePair enId="body" arId="body_ar" persist={persisted(announcement, 'body', 'body_ar')} />
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/announcements">Cancel</Link>
                        </Button>
                        <Button type="submit">{submitLabel}</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

/** Saving through the translate button only makes sense once the row exists. */
function persisted(announcement: AnnouncementDraft | undefined, english: string, arabic: string) {
    return announcement
        ? { table: 'announcements', id: announcement.id, enColumn: english, arColumn: arabic }
        : undefined;
}
