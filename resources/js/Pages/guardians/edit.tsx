import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function GuardiansEdit({ guardian }: { guardian: { id: number; first_name: string; last_name: string; email: string; phone: string; relationship: string; occupation: string; address: string; emergency_contact: string } }) {
    return (
        <AppShell
            title="Edit Guardian"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Guardians', href: '/guardians' },
                { label: 'Edit Guardian' },
            ]}
        >
            <PageHeader
                title="Edit Guardian"
                description={`${guardian.first_name} ${guardian.last_name}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/guardians"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Guardian Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/guardians/${guardian.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="first_name">First Name</Label>
                                <Input id="first_name" name="first_name" defaultValue={guardian.first_name} required />
                            </div>
                            <div>
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input id="last_name" name="last_name" defaultValue={guardian.last_name} required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={guardian.email} required />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" type="tel" defaultValue={guardian.phone} required />
                            </div>
                            <div>
                                <Label htmlFor="relationship">Relationship</Label>
                                <select id="relationship" name="relationship" className="input" required defaultValue={guardian.relationship}>
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="guardian">Guardian</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="occupation">Occupation</Label>
                                <Input id="occupation" name="occupation" defaultValue={guardian.occupation} />
                            </div>
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" name="address" defaultValue={guardian.address} />
                            </div>
                            <div>
                                <Label htmlFor="emergency_contact">Emergency Contact</Label>
                                <Input id="emergency_contact" name="emergency_contact" type="tel" defaultValue={guardian.emergency_contact} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/guardians">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Guardian</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
