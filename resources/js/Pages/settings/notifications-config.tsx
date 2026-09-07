import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function SettingsNotifications() {
    return (
        <AppShell
            title="Notification Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/general' },
                { label: 'Notifications' },
            ]}
        >
            <PageHeader
                title="Notification Settings"
                description="Configure notification preferences for different events"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Email Notifications</CardTitle>
                        <CardDescription>Manage email notification preferences</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form action="/settings/notifications-config" method="POST" className="space-y-6">
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_enrollment">Enrollment Notifications</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails when a student enrolls</p>
                                    </div>
                                    <Input id="email_enrollment" type="checkbox" defaultChecked className="h-5 w-5" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_attendance">Attendance Alerts</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails for attendance issues</p>
                                    </div>
                                    <Input id="email_attendance" type="checkbox" defaultChecked className="h-5 w-5" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_exam">Exam Results</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails when exam results are published</p>
                                    </div>
                                    <Input id="email_exam" type="checkbox" defaultChecked className="h-5 w-5" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_assignment">Assignment Deadlines</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails for upcoming assignment deadlines</p>
                                    </div>
                                    <Input id="email_assignment" type="checkbox" className="h-5 w-5" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_announcement">Announcements</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails for new announcements</p>
                                    </div>
                                    <Input id="email_announcement" type="checkbox" defaultChecked className="h-5 w-5" />
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <Button type="button" variant="outline">Cancel</Button>
                                <Button type="submit">Save Changes</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/general">General Settings</a>
                        </Button>
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/notifications-config">Notifications</a>
                        </Button>
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/email">Email Configuration</a>
                        </Button>
                        <Button variant="outline" className="w-full justify-start" asChild>
                            <a href="/settings/security">Security Settings</a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
