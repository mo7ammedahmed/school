import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AnnouncementsEdit({ announcement }: { announcement: { id: number; title: string; target_audience: string; publish_date: string; expiry_date: string; content: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Edit Announcement"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Announcements', href: '/announcements' },
                { label: 'Edit Announcement' },
            ]}
        >
            <PageHeader
                title="Edit Announcement"
                description={announcement.title}
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
                    <form className="space-y-6" method="POST" action={`/announcements/${announcement.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" defaultValue={announcement.title} required />
                            </div>
                            <div>
                                <Label htmlFor="target_audience">Target Audience</Label>
                                <select id="target_audience" name="target_audience" className="input" required defaultValue={announcement.target_audience}>
                                    <option value="all">All</option>
                                    <option value="students">Students</option>
                                    <option value="teachers">Teachers</option>
                                    <option value="parents">Parents</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="publish_date">Publish Date</Label>
                                <Input id="publish_date" name="publish_date" type="date" defaultValue={announcement.publish_date} required />
                            </div>
                            <div>
                                <Label htmlFor="expiry_date">Expiry Date</Label>
                                <Input id="expiry_date" name="expiry_date" type="date" defaultValue={announcement.expiry_date} required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="content">Content</Label>
                                <textarea id="content" name="content" className="input min-h-[200px]" required defaultValue={announcement.content} />
                            </div>
                            <div>
                                <Label htmlFor="is_active">Active</Label>
                                <select id="is_active" name="is_active" className="input" required defaultValue={String(announcement.is_active)}>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/announcements">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Announcement</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
