import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function TeachersCreate() {
    return (
        <AppShell
            title="Add Teacher"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Teachers', href: '/teachers' },
                { label: 'Add Teacher' },
            ]}
        >
            <PageHeader
                title="Add Teacher"
                description="Register a new teacher"
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
                    <form className="space-y-6" method="POST" action="/teachers">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="first_name">First Name</Label>
                                <Input id="first_name" name="first_name" required />
                            </div>
                            <div>
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input id="last_name" name="last_name" required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" required />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" type="tel" />
                            </div>
                            <div>
                                <Label htmlFor="employee_id">Employee ID</Label>
                                <Input id="employee_id" name="employee_id" required />
                            </div>
                            <div>
                                <Label htmlFor="specialization">Specialization</Label>
                                <Input id="specialization" name="specialization" />
                            </div>
                            <div>
                                <Label htmlFor="qualification">Qualification</Label>
                                <Input id="qualification" name="qualification" />
                            </div>
                            <div>
                                <Label htmlFor="date_of_birth">Date of Birth</Label>
                                <Input id="date_of_birth" name="date_of_birth" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="gender">Gender</Label>
                                <select id="gender" name="gender" className="input" required>
                                    <option value="">Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" name="address" />
                            </div>
                            <div>
                                <Label htmlFor="hire_date">Hire Date</Label>
                                <Input id="hire_date" name="hire_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="active">
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
                            <Button type="submit">Add Teacher</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
