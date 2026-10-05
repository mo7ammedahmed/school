import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { humaniseRole } from '@/lib/utils';

type UserRecord = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    last_login_at: string | null;
    can_update: boolean;
};

export default function UsersShow({ user }: { user: UserRecord }) {
    return (
        <AppShell
            title={user.name}
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Settings', href: '/settings/school' },
                { label: 'Users', href: '/settings/users' },
                { label: user.name },
            ]}
        >
            <PageHeader
                title={user.name}
                description={humaniseRole(user.role)}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/users">
                            <ArrowLeft className="me-2 h-4 w-4" aria-hidden="true" />
                            Back
                        </Link>
                    </Button>
                }
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>User details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="text-sm text-muted-foreground">Email</p>
                            <p className="font-medium">{user.email}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Role</p>
                            <p className="font-medium">{humaniseRole(user.role)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Status</p>
                            {user.is_active ? <Badge>Active</Badge> : <Badge variant="destructive">Inactive</Badge>}
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Last login</p>
                            <p className="font-medium">
                                {user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never'}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Actions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {user.can_update ? <Button asChild>
                            <Link href={`/settings/users/${user.id}/edit`}>Edit this user</Link>
                        </Button> : <p className="text-sm text-muted-foreground">This account requires a platform administrator to make changes.</p>}
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}
