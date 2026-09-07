import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function TeachersEdit({ teacher }: { teacher: { id: number; first_name: string; last_name: string; email: string; phone: string; employee_id: string; specialization: string; qualification: string; date_of_birth: string; gender: string; address: string; hire_date: string; status: string } }) {
    return (
        <AppShell
            title="Edit Teacher"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Teachers', href: '/teachers' },
                { label: 'Edit Teacher' },
            ]}
        >
            <PageHeader
                title="Edit Teacher"
                description={`${teacher.first_name} ${teacher.last_name}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/teachers"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Teacher Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/teachers/${teacher.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="first_name">First Name</Label>
                                <Input id="first_name" name="first_name" defaultValue={teacher.first_name} required />
                            </div>
                            <div>
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input id="last_name" name="last_name" defaultValue={teacher.last_name} required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={teacher.email} required />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" type="tel" defaultValue={teacher.phone} />
                            </div>
                            <div>
                                <Label htmlFor="employee_id">Employee ID</Label>
                                <Input id="employee_id" name="employee_id" defaultValue={teacher.employee_id} required />
                            </div>
                            <div>
                                <Label htmlFor="specialization">Specialization</Label>
                                <Input id="specialization" name="specialization" defaultValue={teacher.specialization} />
                            </div>
                            <div>
                                <Label htmlFor="qualification">Qualification</Label>
                                <Input id="qualification" name="qualification" defaultValue={teacher.qualification} />
                            </div>
                            <div>
                                <Label htmlFor="date_of_birth">Date of Birth</Label>
                                <Input id="date_of_birth" name="date_of_birth" type="date" defaultValue={teacher.date_of_birth} required />
                            </div>
                            <div>
                                <Label htmlFor="gender">Gender</Label>
                                <select id="gender" name="gender" className="input" required defaultValue={teacher.gender}>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" name="address" defaultValue={teacher.address} />
                            </div>
                            <div>
                                <Label htmlFor="hire_date">Hire Date</Label>
                                <Input id="hire_date" name="hire_date" type="date" defaultValue={teacher.hire_date} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={teacher.status}>
                                    <option value="active">Active</option>
                                    <option value="on_leave">On Leave</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/teachers">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Teacher</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
