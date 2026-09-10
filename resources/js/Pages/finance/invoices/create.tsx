import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function FinanceInvoicesCreate({ students }: { students: { id: number; name: string }[] }) {
    return (
        <AppShell
            title="Create Invoice"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Finance', href: '/finance/invoices' },
                { label: 'Invoices', href: '/finance/invoices' },
                { label: 'Create Invoice' },
            ]}
        >
            <PageHeader
                title="Create Invoice"
                description="Add a new invoice"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/finance/invoices"><ArrowLeft className="mr-2 h-4 w-4" />Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Invoice Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6" method="POST" action="/finance/invoices">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <Label htmlFor="student_id">Student</Label>
                                <select id="student_id" name="student_id" className="input" required>
                                    <option value="">Select student</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="invoice_number">Invoice Number</Label>
                                <Input id="invoice_number" name="invoice_number" required />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select id="status" name="status" className="input" required defaultValue="draft">
                                    <option value="draft">Draft</option>
                                    <option value="issued">Issued</option>
                                    <option value="paid">Paid</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="void">Void</option>
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="issue_date">Issue Date</Label>
                                <Input id="issue_date" name="issue_date" type="date" />
                            </div>
                            <div>
                                <Label htmlFor="due_date">Due Date</Label>
                                <Input id="due_date" name="due_date" type="date" required />
                            </div>
                            <div>
                                <Label htmlFor="subtotal">Subtotal</Label>
                                <Input id="subtotal" name="subtotal" type="number" step="0.01" required />
                            </div>
                            <div>
                                <Label htmlFor="tax_rate">Tax Rate (%)</Label>
                                <Input id="tax_rate" name="tax_rate" type="number" step="0.01" defaultValue="0" />
                            </div>
                            <div>
                                <Label htmlFor="discount_amount">Discount Amount</Label>
                                <Input id="discount_amount" name="discount_amount" type="number" step="0.01" defaultValue="0" />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <textarea id="notes" name="notes" className="input min-h-[100px]" />
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/finance/invoices">Cancel</Link>
                            </Button>
                            <Button type="submit">Create Invoice</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppShell>
    );
}
