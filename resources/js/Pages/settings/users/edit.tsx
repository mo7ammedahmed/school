// Facts for this file:
// 1. Called by: routes/web.php (GET /settings/users/{user}/edit).
// 2. Data flow: posts `_method=PUT` with `roles[]` (role ids) and `is_active`;
//    both used to be free-text selects the controller silently ignored, so a
//    role or status change never reached the database.
// 3. `role_ids` comes from UserController@edit so the current role is preselected.

import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { FormFeedback } from '@/components/ui/form-feedback';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { humaniseRole } from '@/lib/utils';

type Role = { id: number; name: string };

type EditableUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    role_ids: number[];
};

export default function UsersEdit({ user, roles }: { user: EditableUser; roles: Role[] }) {
    return (
        <AppShell
            title="Edit User"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Users', href: '/settings/users' },
                { label: 'Edit User' },
            ]}
        >
            <PageHeader
                title="Edit user"
                description={user.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/users">
                            <ArrowLeft className="me-2 h-4 w-4" aria-hidden="true" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Account</CardTitle>
                    <CardDescription>Leave the password fields empty to keep the current password.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-5" method="POST" action={`/settings/users/${user.id}`}>
                        <input type="hidden" name="_method" value="PUT" />

                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Full name</Label>
                                <Input id="name" name="name" defaultValue={user.name} required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={user.email} required />
                            </div>
                            <div>
                                <Label htmlFor="password">New password</Label>
                                <Input id="password" name="password" type="password" autoComplete="new-password" />
                                <p className="mt-1.5 text-xs text-muted-foreground">At least 8 characters.</p>
                            </div>
                            <div>
                                <Label htmlFor="password_confirmation">Confirm new password</Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                />
                            </div>
                            <div>
                                <Label htmlFor="roles">Role</Label>
                                <Select id="roles" name="roles[]" defaultValue={String(user.role_ids[0] ?? '')}>
                                    <option value="">Use the current role ({humaniseRole(user.role)})</option>
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {humaniseRole(role.name)}
                                        </option>
                                    ))}
                                </Select>
                                <p className="mt-1.5 text-xs text-muted-foreground">
                                    The chosen role becomes this user&apos;s role at this school.
                                </p>
                            </div>
                            <div>
                                <Label htmlFor="is_active">Status</Label>
                                <Select id="is_active" name="is_active" defaultValue={user.is_active ? '1' : '0'}>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                                <p className="mt-1.5 text-xs text-muted-foreground">
                                    Inactive accounts keep their history but cannot sign in.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            <Button type="submit">Update user</Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/settings/users">Cancel</Link>
                            </Button>
                            <FormFeedback />
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
