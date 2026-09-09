import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function RoomsEdit({ room }: { room: { id: number; name: string; name_ar: string; name_en: string; code: string; room_type: string; capacity: number; description: string } }) {
    return (
        <AppShell
            title="Edit Room"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Rooms', href: '/rooms' },
                { label: 'Edit Room' },
            ]}
        >
            <PageHeader
                title="Edit Room"
                description={room.name}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/rooms"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Room Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action={`/rooms/${room.id}`}>
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_ar">Room Name (Arabic)</Label>
                                <Input id="name_ar" name="name_ar" defaultValue={room.name_ar} required />
                            </div>
                            <div>
                                <Label htmlFor="name_en">Room Name (English)</Label>
                                <Input id="name_en" name="name_en" defaultValue={room.name_en} required />
                            </div>
                            <div>
                                <Label htmlFor="code">Room Code</Label>
                                <Input id="code" name="code" defaultValue={room.code} required />
                            </div>
                            <div>
                                <Label htmlFor="room_type">Room Type</Label>
                                <select id="room_type" name="room_type" className="input" required defaultValue={room.room_type}>
                                    <option value="classroom">Classroom</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="library">Library</option>
                                    <option value="gymnasium">Gymnasium</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="office">Office</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="capacity">Capacity</Label>
                                <Input id="capacity" name="capacity" type="number" defaultValue={room.capacity} required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea id="description" name="description" className="input min-h-[100px]" defaultValue={room.description} />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/rooms">Cancel</Link>
                            </Button>
                            <Button type="submit">Update Room</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
