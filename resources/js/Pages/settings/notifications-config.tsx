import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { FormFeedback } from '@/components/ui/form-feedback';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

type NotificationSettings = {
    email_enrollment?: boolean;
    email_attendance?: boolean;
    email_exam?: boolean;
    email_assignment?: boolean;
    email_announcement?: boolean;
};

export default function SettingsNotifications({ settings = {} }: { settings?: NotificationSettings }) {
    return (
        <AppShell
            title="Notification Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
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
                                    <Checkbox id="email_enrollment" name="email_enrollment" value="1" defaultChecked={settings.email_enrollment ?? true} className="shrink-0" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_attendance">Attendance Alerts</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails for attendance issues</p>
                                    </div>
                                    <Checkbox id="email_attendance" name="email_attendance" value="1" defaultChecked={settings.email_attendance ?? true} className="shrink-0" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_exam">Exam Results</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails when exam results are published</p>
                                    </div>
                                    <Checkbox id="email_exam" name="email_exam" value="1" defaultChecked={settings.email_exam ?? true} className="shrink-0" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_assignment">Assignment Deadlines</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails for upcoming assignment deadlines</p>
                                    </div>
                                    <Checkbox id="email_assignment" name="email_assignment" value="1" defaultChecked={settings.email_assignment ?? false} className="shrink-0" />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="email_announcement">Announcements</Label>
                                        <p className="text-sm text-muted-foreground">Receive emails for new announcements</p>
                                    </div>
                                    <Checkbox id="email_announcement" name="email_announcement" value="1" defaultChecked={settings.email_announcement ?? true} className="shrink-0" />
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-4">
                                <Button type="submit">Save Changes</Button>
                                <Button type="button" variant="outline" asChild>
                                    <a href="/settings/school">Cancel</a>
                                </Button>
                                <FormFeedback showErrors={false} />
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
                            <a href="/settings/school">General Settings</a>
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
