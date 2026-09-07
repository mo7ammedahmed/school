import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ClassroomsShow({ classroom }: { classroom: { id: number; name: string; section: { name: string }; teacher: { first_name: string; last_name: string }; capacity: number; room_number: string; building: string } }) {
    return (
        <AppShell
            title="Classroom Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Classrooms', href: '/classrooms' },
                { label: classroom.name },
            ]}
        >
            <PageHeader
                title="Classroom Details"
                description={classroom.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/classrooms"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/classrooms/${classroom.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Classroom Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Classroom Name</span>
                            <p className="text-base">{classroom.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Section</span>
                            <p className="text-base">{classroom.section.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Teacher</span>
                            <p className="text-base">{classroom.teacher.first_name} {classroom.teacher.last_name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Capacity</span>
                            <p className="text-base">{classroom.capacity}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Room Number</span>
                            <p className="text-base">{classroom.room_number || '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Building</span>
                            <p className="text-base">{classroom.building || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
