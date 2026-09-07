import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function StudentsEdit({ student, guardians }: { student: { id: number; first_name: string; last_name: string; email: string; phone: string; date_of_birth: string; gender: string; address: string; guardian_id: number; enrollment_date: string; status: string }; guardians: { id: number; first_name: string; last_name: string }[] }) {
    return (
        <AppShell
            title="Edit Student"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Students', href: '/students' },
                { label: 'Edit Student' },
            ]}
        >
            <PageHeader
                title="Edit Student"
                description={`${student.first_name} ${student.last_name}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/students"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Student Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/students/${student.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="first_name">First Name</Label>
                                <Input id="first_name" name="first_name" defaultValue={student.first_name} required />
                            </div>
                            <div>
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input id="last_name" name="last_name" defaultValue={student.last_name} required />
                            </div>
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={student.email} required />
                            </div>
                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" type="tel" defaultValue={student.phone} />
                            </div>
                            <div>
                                <Label htmlFor="date_of_birth">Date of Birth</Label>
                                <Input id="date_of_birth" name="date_of_birth" type="date" defaultValue={student.date_of_birth} required />
                            </div>
                            <div>
                                <Label htmlFor="gender">Gender</Label>
                                <select id="gender" name="gender" className="input" required defaultValue={student.gender}>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="address">Address</Label>
                                <Input id="address" name="address" defaultValue={student.address} />
                            </div>
                            <div>
                                <Label htmlFor="guardian_id">Guardian</Label>
                                <select id="guardian_id" name="guardian_id" className="input" required defaultValue={student.guardian_id}>
                                    <option value="">Select guardian</option>
                                    {guardians.map((guardian) => (
                                        <option key={guardian.id} value={guardian.id}>{guardian.first_name} {guardian.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="enrollment_date">Enrollment Date</Label>
                                <Input id="enrollment_date" name="enrollment_date" type="date" defaultValue={student.enrollment_date} required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue={student.status}>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="graduated">Graduated</option>
                                    <option value="withdrawn">Withdrawn</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/students">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Student</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
