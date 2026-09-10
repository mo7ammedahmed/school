import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function SettingsGeneral() {
    return (
        <AppShell
            title="General Settings"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings' },
            ]}
        >
            <PageHeader
                title="General Settings"
                description="Manage your school's general configuration"
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>School Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-6">
                            <div>
                                <Label htmlFor="school_name">School Name</Label>
                                <Input id="school_name" defaultValue="Al Noor School" />
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="email">Email</Label>
                                    <Input id="email" type="email" defaultValue="info@alnoor.school" />
                                </div>
                                <div>
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input id="phone" type="tel" defaultValue="+966501234567" />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" defaultValue="123 Education Street, Riyadh" />
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
                        <Button variant="outline" className="w-full justify-start">
                            School Information
                        </Button>
                        <Button variant="outline" className="w-full justify-start">
                            Academic Settings
                        </Button>
                        <Button variant="outline" className="w-full justify-start">
                            Notification Settings
                        </Button>
                        <Button variant="outline" className="w-full justify-start">
                            Security Settings
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
