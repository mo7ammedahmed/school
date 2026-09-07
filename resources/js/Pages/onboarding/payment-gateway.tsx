import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function OnboardingPaymentGateway() {
    return (
        <AppShell
            title="Payment Gateway Setup"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Onboarding', href: '/onboarding' },
                { label: 'Payment Gateway' },
            ]}
        >
            <PageHeader
                title="Payment Gateway Setup"
                description="Configure payment processing"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Payment Gateway</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p className="text-sm text-blue-800">
                                You can configure payment gateways (Moyasar, Hyperpay, Stripe) later in the
                                Finance settings. For now, you can use offline payment methods.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="p-4 border rounded-lg">
                                <h3 className="font-medium">Moyasar</h3>
                                <p className="text-sm text-gray-600">Saudi Arabia</p>
                            </div>
                            <div className="p-4 border rounded-lg">
                                <h3 className="font-medium">Hyperpay</h3>
                                <p className="text-sm text-gray-600">Middle East</p>
                            </div>
                            <div className="p-4 border rounded-lg">
                                <h3 className="font-medium">Stripe</h3>
                                <p className="text-sm text-gray-600">Global</p>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-between mt-6">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/onboarding/fee-structure"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href="/onboarding/finish">Skip for Now<ArrowRight className="ml-2 h-4 w-4" /></Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </AppShell>
    );
}