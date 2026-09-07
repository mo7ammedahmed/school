import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardiansShow({ guardian }: { guardian: { id: number; first_name: string; last_name: string; email: string; phone: string; relationship: string; occupation: string; address: string; emergency_contact: string } }) {
    return (
        <AppShell
            title="Guardian Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Guardians', href: '/guardians' },
                { label: `${guardian.first_name} ${guardian.last_name}` },
            ]}
        >
            <PageHeader
                title="Guardian Details"
                description={`${guardian.first_name} ${guardian.last_name}`}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/guardians"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/guardians/${guardian.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Guardian Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Full Name</span>
                            <p className="text-base">{guardian.first_name} {guardian.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Email</span>
                            <p className="text-base">{guardian.email}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Phone</span>
                            <p className="text-base">{guardian.phone}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Relationship</span>
                            <p className="text-base capitalize">{guardian.relationship}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Occupation</span>
                            <p className="text-base">{guardian.occupation || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Address</span>
                            <p className="text-base">{guardian.address || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Emergency Contact</span>
                            <p className="text-base">{guardian.emergency_contact || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
