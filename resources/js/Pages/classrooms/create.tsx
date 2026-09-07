import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ClassroomsCreate({ sections, teachers }: { sections: { id: number; name: string }[]; teachers: { id: number; first_name: string; last_name: string }[] }) {
    return (
        <AppShell
            title="Add Classroom"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Classrooms', href: '/classrooms' },
                { label: 'Add Classroom' },
            ]}
        >
            <PageHeader
                title="Add Classroom"
                description="Create a new classroom"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/classrooms"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Classroom Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/classrooms">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Classroom Name</Label>
                                <Input id="name" name="name" required />
                            </div>
                            <div>
                                <Label htmlFor="section_id">Section</Label>
                                <select id="section_id" name="section_id" className="input" required>
                                    <option value="">Select section</option>
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id}>{section.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="teacher_id">Teacher</Label>
                                <select id="teacher_id" name="teacher_id" className="input" required>
                                    <option value="">Select teacher</option>
                                    {teachers.map((teacher) => (
                                        <option key={teacher.id} value={teacher.id}>{teacher.first_name} {teacher.last_name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="capacity">Capacity</Label>
                                <Input id="capacity" name="capacity" type="number" required />
                            </div>
                            <div>
                                <Label htmlFor="room_number">Room Number</Label>
                                <Input id="room_number" name="room_number" />
                            </div>
                            <div>
                                <Label htmlFor="building">Building</Label>
                                <Input id="building" name="building" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/classrooms">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Classroom</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
