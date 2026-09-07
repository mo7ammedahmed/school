import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Apply() {
    const steps = [
        { name: 'Start Application', href: '/apply/start', description: 'Begin your application process' },
        { name: 'Guardian Information', href: '/apply/guardian', description: 'Provide guardian details' },
        { name: 'Student Information', href: '/apply/student', description: 'Enter student information' },
        { name: 'Previous School', href: '/apply/previous-school', description: 'Share previous school details' },
        { name: 'Documents', href: '/apply/documents', description: 'Upload required documents' },
        { name: 'Review & Submit', href: '/apply/review', description: 'Review and submit application' },
    ];

    return (
        <AppShell
            title="Apply"
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Apply' },
            ]}
        >
            <PageHeader
                title="Application Process"
                description="Complete the following steps to apply"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Application Steps</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {steps.map((step, index) => (
                            <Link key={step.name} href={step.href}>
                                <div className="flex items-center gap-4 p-4 rounded-lg border hover:bg-gray-50 transition">
                                    <div className="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold">
                                        {index + 1}
                                    </div>
                                    <div className="flex-grow">
                                        <h3 className="font-medium">{step.name}</h3>
                                        <p className="text-sm text-gray-600">{step.description}</p>
                                    </div>
                                    <ArrowRight className="h-5 w-5 text-gray-400" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </CardContent>
            </Card>

            <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div className="flex items-start gap-3">
                    <CheckCircle className="h-5 w-5 text-blue-600 mt-0.5" />
                    <div>
                        <h3 className="font-medium text-blue-900">Need Help?</h3>
                        <p className="text-sm text-blue-700">
                            Contact our admissions office at admissions@school.edu or call +1 (555) 123-4567
                            for assistance with your application.
                        </p>
                    </div>
                </div>
            </div>
        </AppShell>
    );
}
