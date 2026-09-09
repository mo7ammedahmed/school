import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function RoomsCreate() {
    return (
        <AppShell
            title="Add Room"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Rooms', href: '/rooms' },
                { label: 'Add Room' },
            ]}
        >
            <PageHeader
                title="Add Room"
                description="Create a new room"
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
                    <form className="space-y-6" method="POST" action="/rooms">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name_ar">Room Name (Arabic)</Label>
                                <Input id="name_ar" name="name_ar" required />
                            </div>
                            <div>
                                <Label htmlFor="name_en">Room Name (English)</Label>
                                <Input id="name_en" name="name_en" required />
                            </div>
                            <div>
                                <Label htmlFor="code">Room Code</Label>
                                <Input id="code" name="code" required />
                            </div>
                            <div>
                                <Label htmlFor="room_type">Room Type</Label>
                                <select id="room_type" name="room_type" className="input" required>
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
                                <Input id="capacity" name="capacity" type="number" required />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea id="description" name="description" className="input min-h-[100px]" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/rooms">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Room</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
