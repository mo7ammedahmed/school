import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

export default function Apply() {
    return (
        <PublicLayout>
            <div className="py-20">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold tracking-tight">Apply to Al Noor School</h1>
                        <p className="mt-4 text-lg text-muted-foreground">
                            Start your application today and join our community of learners.
                        </p>
                    </div>

                    <div className="mt-12 rounded-lg border bg-card p-8">
                        <h2 className="text-2xl font-semibold">Application Process</h2>
                        <p className="mt-2 text-muted-foreground">
                            Our application process consists of 6 simple steps. You can save your progress and return later.
                        </p>

                        <div className="mt-8 space-y-4">
                            {[
                                'Start Application',
                                'Guardian Information',
                                'Student Information',
                                'Previous School',
                                'Documents',
                                'Review & Submit',
                            ].map((step, index) => (
                                <div key={step} className="flex items-center gap-4">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                        <span className="text-sm font-bold">{index + 1}</span>
                                    </div>
                                    <span className="font-medium">{step}</span>
                                </div>
                            ))}
                        </div>

                        <Button size="lg" className="mt-8 w-full" asChild>
                            <Link href="/apply/start">Start Application</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
