import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function StudentsCreate({ guardians }: { guardians: { id: number; first_name: string; last_name: string }[] }) {
    return (
        <AppShell
            title="Add Student"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Students', href: '/students' },
                { label: 'Add Student' },
            ]}
        >
            <PageHeader
                title="Add Student"
                description="Register a new student"
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
                    <form className="space-y-6" method="POST" action="/students">
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
                                <Label htmlFor="student_id_number">Student ID</Label>
                                <Input id="student_id_number" name="student_id_number" required />
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
                                <Label htmlFor="guardian_id">Guardian</Label>
                                <select id="guardian_id" name="guardian_id" className="input" required>
                                    <option value="">Select guardian</option>
                                    {guardians.map((guardian) => (
                                        <option key={guardian.id} value={guardian.id}>{guardian.first_name} {guardian.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="enrollment_date">Enrollment Date</Label>
                                <Input id="enrollment_date" name="enrollment_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="active">
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
                            <Button type="submit">Add Student</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
