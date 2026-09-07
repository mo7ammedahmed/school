import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ApplyReview() {
    return (
        <AppShell
            title="Review Application"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Review' },
            ]}
        >
            <PageHeader
                title="Review Application"
                description="Please review your application details before submitting"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Application Summary</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-6">
                        <div>
                            <h3 className="font-semibold mb-2">Guardian Information</h3>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p><strong>Name:</strong> John Doe</p>
                                <p><strong>Email:</strong> john.doe@example.com</p>
                                <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                                <p><strong>Relationship:</strong> Father</p>
                            </div>
                        </div>

                        <div>
                            <h3 className="font-semibold mb-2">Student Information</h3>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p><strong>Name:</strong> Jane Doe</p>
                                <p><strong>Date of Birth:</strong> 2015-03-15</p>
                                <p><strong>Gender:</strong> Female</p>
                                <p><strong>Nationality:</strong> American</p>
                            </div>
                        </div>

                        <div>
                            <h3 className="font-semibold mb-2">Previous School</h3>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p><strong>School Name:</strong> ABC Elementary</p>
                                <p><strong>Last Grade:</strong> 4</p>
                            </div>
                        </div>

                        <div>
                            <h3 className="font-semibold mb-2">Uploaded Documents</h3>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <ul className="space-y-1">
                                    <li>• Birth Certificate</li>
                                    <li>• Previous School Records</li>
                                    <li>• Passport Photos</li>
                                    <li>• Medical Records</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="mt-6 flex justify-between">
                <Button variant="outline" asChild>
                    <Link href="/apply/documents"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                </Button>
                <Button asChild>
                    <Link href="/apply/submitted">Submit Application<ArrowRight className="ml-2 h-4 w-4" /></Link>
                </Button>
            </div>
        </AppShell>
    );
}
