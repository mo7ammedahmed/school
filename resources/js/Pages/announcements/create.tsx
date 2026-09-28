import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AnnouncementsCreate() {
    return (
        <AppShell
            title="New Announcement"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements', href: '/announcements' },
                { label: 'New Announcement' },
            ]}
        >
            <PageHeader
                title="New Announcement"
                description="Create a new announcement"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/announcements"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Announcement Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/announcements">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Title (English)</Label>
                                <Input id="title" name="title" />
                            </div>
                            <div>
                                <Label htmlFor="title_ar">Title (Arabic)</Label>
                                <Input id="title_ar" name="title_ar" dir="rtl" />
                            </div>
                            <div className="md:col-span-2">
                                <TranslatePair enId="title" arId="title_ar" />
                            </div>
                            <div>
                                <Label htmlFor="target_audience">Target Audience</Label>
                                <select id="target_audience" name="target_audience" className="input" required>
                                    <option value="all">All</option>
                                    <option value="students">Students</option>
                                    <option value="teachers">Teachers</option>
                                    <option value="parents">Parents</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="is_published">Published</Label>
                                <select id="is_published" name="is_published" className="input" required defaultValue="1">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input id="start_date" name="start_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="end_date">End Date</Label>
                                <Input id="end_date" name="end_date" type="date" required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="body">Body (English)</Label>
                                <textarea id="body" name="body" className="input min-h-[200px]" />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="body_ar">Body (Arabic)</Label>
                                <textarea id="body_ar" name="body_ar" dir="rtl" className="input min-h-[200px]" />
                            </div>
                            <div className="md:col-span-2">
                                <TranslatePair enId="body" arId="body_ar" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/announcements">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Announcement</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
