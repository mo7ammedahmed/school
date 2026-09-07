import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function UsersEdit({ user }: { user: { id: number; name: string; email: string; role: string; is_active: boolean } }) {
    return (
        <AppShell
            title="Edit User"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings' },
                { label: 'Users', href: '/settings/users' },
                { label: 'Edit User' },
            ]}
        >
            <PageHeader
                title="Edit User"
                description={user.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/users"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>User Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/settings/users/${user.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Full Name</Label>
                                <Input id="name" name="name" defaultValue={user.name} required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={user.email} required />
                            </div>
                            <div>
                                <Label htmlFor="password">New Password</Label>
                                <Input id="password" name="password" type="password" />
                            </div>
                            <div>
                                <Label htmlFor="password_confirmation">Confirm New Password</Label>
                                <Input id="password_confirmation" name="password_confirmation" type="password" />
                            </div>
                            <div>
                                <Label htmlFor="role">Role</Label>
                                <select id="role" name="role" className="input" required defaultValue={user.role}>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="student">Student</option>
                                    <option value="guardian">Guardian</option>
                                    <option value="accountant">Accountant</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="is_active">Active</Label>
                                <select id="is_active" name="is_active" className="input" required defaultValue={String(user.is_active)}>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/users">Cancel</Link>
                            </Button>
                            <Button type="submit">Update User</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
