import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function RoomsShow({ room }: { room: { id: number; name: string; code: string; room_type: string; capacity: number; description: string } }) {
    return (
        <AppShell
            title="Room Details"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Rooms', href: '/rooms' },
                { label: room.name },
            ]}
        >
            <PageHeader
                title="Room Details"
                description={room.name}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/rooms"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/rooms/${room.id}/edit`}>Edit</Link>
                        </Button>
                    </div>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Room Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Room Name</span>
                            <p className="text-base">{room.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Code</span>
                            <p className="text-base">{room.code}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Room Type</span>
                            <p className="text-base capitalize">{room.room_type}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Capacity</span>
                            <p className="text-base">{room.capacity}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-sm font-medium text-muted-foreground">Description</span>
                            <p className="text-base">{room.description || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
