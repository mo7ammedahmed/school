// Facts for this file:
// 1. Called by: routes/web.php (GET /settings/users/create).
// 2. Data flow: posts a plain multipart form; UserController@store validates
//    `roles.*` against the roles table, so the select posts role ids and
//    ignores the old hardcoded names that were silently discarded.
// 3. Role and active flag belong to this school's membership row, which is what
//    the list and edit screens read back.

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

export default function UsersCreate({ roles }: { roles: Role[] }) {
    return (
        <AppShell
            title="New User"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Users', href: '/settings/users' },
                { label: 'New User' },
            ]}
        >
            <PageHeader
                title="New user"
                description="Create an account and give it a role at this school."
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
                    <CardTitle>User information</CardTitle>
                    <CardDescription>The account is created for the school you are currently signed in to.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-5" method="POST" action="/settings/users">
                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Full name</Label>
                                <Input id="name" name="name" required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" required />
                            </div>
                            <div>
                                <Label htmlFor="password">Password</Label>
                                <Input id="password" name="password" type="password" autoComplete="new-password" required />
                                <p className="mt-1.5 text-xs text-muted-foreground">At least 8 characters.</p>
                            </div>
                            <div>
                                <Label htmlFor="password_confirmation">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="roles">Role</Label>
                                <Select id="roles" name="roles[]" defaultValue="" required>
                                    <option value="" disabled>
                                        Select a role
                                    </option>
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
                                <Select id="is_active" name="is_active" defaultValue="1">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                                <p className="mt-1.5 text-xs text-muted-foreground">
                                    Inactive accounts keep their history but cannot sign in.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            <Button type="submit">Create user</Button>
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
