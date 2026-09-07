import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Home } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ApplySubmitted() {
    return (
        <AppShell
            title="Application Submitted"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply', href: '/apply' },
                { label: 'Submitted' },
            ]}
        >
            <PageHeader
                title="Application Submitted!"
                description="Thank you for your application"
            />

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CheckCircle className="h-6 w-6 text-green-600" />
                        Application Received
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-8">
                        <CheckCircle className="h-16 w-16 text-green-600 mx-auto mb-4" />
                        <h2 className="text-2xl font-bold mb-2">Thank You!</h2>
                        <p className="text-gray-600 mb-6">
                            Your application has been submitted successfully. Our admissions team will review
                            your application and contact you within 5-7 business days.
                        </p>
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p className="text-sm text-blue-800">
                                <strong>Application Reference:</strong> #APP-2024-001234
                            </p>
                            <p className="text-sm text-blue-800 mt-1">
                                <strong>Next Steps:</strong> Please keep your reference number for future correspondence.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/"><Home className="mr-2 h-4 w-4" />Return Home</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}
