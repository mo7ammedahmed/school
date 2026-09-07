import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, Check, X, AlertCircle, FileText, Users } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface ApplicationEvent {
  id: number;
  event_type: string;
  notes?: string;
  created_at: string;
  user: {
    id: number;
    name: string;
  } | null;
}

interface Application {
  id: number;
  reference: string;
  status: string;
  priority: string;
  student_first_name: string;
  student_last_name: string;
  student_date_of_birth?: string;
  student_gender?: string;
  student_nationality?: string;
  grade_applying?: string;
  guardian_first_name: string;
  guardian_last_name: string;
  guardian_email: string;
  guardian_phone?: string;
  guardian_relationship?: string;
  guardian_national_id?: string;
  previous_school_name?: string;
  previous_school_last_grade?: string;
  previous_school_year_completed?: string;
  documents: { name: string; path: string; type?: string }[];
  review_notes?: string;
  internal_notes?: string;
  submitted_at?: string;
  created_at: string;
  updated_at: string;
  events: ApplicationEvent[];
  assigner?: {
    id: number;
    name: string;
  };
  reviewed_by?: {
    first_name: string;
    last_name: string;
  };
  reviewed_at?: string;
  student_notes?: string;
}

const statusVariant: Record<string, string> = {
  draft: 'outline',
  submitted: 'info',
  under_review: 'warning',
  approved: 'success',
  rejected: 'destructive',
  converted: 'default',
  withdrawn: 'outline',
};

const priorityVariant: Record<string, string> = {
  low: 'secondary',
  medium: 'secondary',
  high: 'warning',
  urgent: 'destructive',
};

export default function AdmissionsReviewShow({ application }: { application: Application }) {
  const canDecide = application.status === 'submitted' || application.status === 'under_review';

  return (
    <AppShell
      title={`Application ${application.reference}`}
      breadcrumbs={[
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Admissions', href: '/admissions/applications' },
        { label: 'Review', href: '/admissions/review' },
        { label: application.reference },
      ]}
    >
      <PageHeader
        title={application.reference}
        description={`Review application for ${application.student_first_name} ${application.student_last_name}`}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href="/admissions/review">
                <ArrowLeft className="mr-2 h-4 w-4" />Back to Review Queue
              </Link>
            </Button>
            {canDecide && (
              <>
                <Button variant="destructive" form="decision-form" type="submit" name="decision" value="rejected">
                  <X className="mr-2 h-4 w-4" />Reject
                </Button>
                <Button variant="default" form="decision-form" type="submit" name="decision" value="approved">
                  <Check className="mr-2 h-4 w-4" />Approve
                </Button>
              </>
            )}
          </div>
        }
      />

      <div className="mt-6 grid gap-6 lg:grid-cols-4">
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Application Status</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="text-center">
              <span
                className={`inline-flex rounded-full px-3 py-1.5 text-xs font-medium ${
                  statusVariant[application.status] === 'destructive' ? 'bg-red-100 text-red-800' :
                  statusVariant[application.status] === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                  statusVariant[application.status] === 'success' ? 'bg-green-100 text-green-800' :
                  statusVariant[application.status] === 'info' ? 'bg-blue-100 text-blue-800' :
                  'bg-gray-100 text-gray-800'
                }`}
              >
                {application.status.replace('_', ' ').toUpperCase()}
              </span>
            </div>
            {application.submitted_at && (
              <p className="text-sm text-muted-foreground mt-4">
                Submitted {new Date(application.submitted_at).toLocaleDateString()}
              </p>
            )}
            {application.reviewed_at && (
              <p className="text-sm text-muted-foreground">
                Reviewed {new Date(application.reviewed_at).toLocaleDateString()}
                {application.reviewed_by ? ` by ${application.reviewed_by.first_name} ${application.reviewed_by.last_name}` : ''}
              </p>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Priority</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center space-x-3">
              <div className={`w-2.5 h-2.5 rounded-full ${
                priorityVariant[application.priority] === 'destructive' ? 'bg-red-500' :
                priorityVariant[application.priority] === 'warning' ? 'bg-yellow-500' :
                priorityVariant[application.priority] === 'success' ? 'bg-green-500' :
                'bg-blue-500'
              }`} />
              <span className="font-medium text-sm">
                {application.priority.charAt(0).toUpperCase() + application.priority.slice(1)}
              </span>
            </div>
            <p className="text-sm text-muted-foreground mt-2">
              Set priority level for this application
            </p>
            <form method="POST" action={`/admissions/review/${application.id}/priority`}>
              <div className="mt-4">
                <Label htmlFor="priority">Priority Level</Label>
                <select id="priority" name="priority" defaultValue={application.priority} className="input w-full mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
                <Button type="submit" className="w-full mt-2">
                  Update Priority
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Assignment</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {application.assigner ? (
              <div className="flex items-center space-x-3">
                <Users className="h-5 w-5" />
                <div>
                  <p className="font-medium">{application.assigner.name}</p>
                  <p className="text-sm text-muted-foreground">Assigned Reviewer</p>
                </div>
              </div>
            ) : (
              <div className="text-center py-4">
                <AlertCircle className="h-6 w-6 text-muted-foreground mb-2" />
                <p className="text-sm">Not assigned to any reviewer</p>
                <Button variant="outline" size="sm">
                  Assign Reviewer
                </Button>
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Review Info</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <span className="text-sm">Days in Queue:</span>
              <span className="font-medium text-sm">
                {application.submitted_at ?
                  Math.floor((new Date().getTime() - new Date(application.submitted_at).getTime()) / (1000 * 60 * 60 * 24)) :
                  '—'
                } days
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm">Last Updated:</span>
              <span className="text-sm text-muted-foreground">
                {new Date(application.updated_at).toLocaleDateString()}
              </span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Separator className="my-6" />

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Applicant Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>First Name</Label>
              <p className="font-medium">{application.student_first_name}</p>
            </div>
            <div className="space-y-2">
              <Label>Last Name</Label>
              <p className="font-medium">{application.student_last_name}</p>
            </div>
            <div className="space-y-2">
              <Label>Date of Birth</Label>
              <p className="font-medium">{application.student_date_of_birth ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>Gender</Label>
              <p className="font-medium">{application.student_gender ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>Nationality</Label>
              <p className="font-medium">{application.student_nationality ?? '—'}</p>
            </div>
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Guardian Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>First Name</Label>
              <p className="font-medium">{application.guardian_first_name}</p>
            </div>
            <div className="space-y-2">
              <Label>Last Name</Label>
              <p className="font-medium">{application.guardian_last_name}</p>
            </div>
            <div className="space-y-2">
              <Label>Email</Label>
              <p className="font-medium">{application.guardian_email}</p>
            </div>
            <div className="space-y-2">
              <Label>Phone</Label>
              <p className="font-medium">{application.guardian_phone ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>Relationship</Label>
              <p className="font-medium">{application.guardian_relationship ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>National ID</Label>
              <p className="font-medium">{application.guardian_national_id ?? '—'}</p>
            </div>
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Application Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Grade Applying For</Label>
              <p className="font-medium">{application.grade_applying ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>Previous School</Label>
              <p className="font-medium">{application.previous_school_name ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>Last Grade Completed</Label>
              <p className="font-medium">{application.previous_school_last_grade ?? '—'}</p>
            </div>
            <div className="space-y-2">
              <Label>Year Completed</Label>
              <p className="font-medium">{application.previous_school_year_completed ?? '—'}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <Separator className="my-6" />

      <Card>
        <CardHeader>
          <CardTitle>Documents</CardTitle>
        </CardHeader>
        <CardContent>
          {(application.documents ?? []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No documents uploaded.</p>
          ) : (
            <div className="space-y-3">
              {application.documents.map((doc, index) => (
                <div key={doc.name + index} className="flex items-center justify-between p-3 border rounded">
                  <div className="flex-1">
                    <FileText className="mr-3 h-4 w-4" />
                    <div>
                      <p className="font-medium">{doc.name}</p>
                      <p className="text-sm text-muted-foreground">
                        {doc.type?.toUpperCase() ?? 'DOCUMENT'}
                      </p>
                    </div>
                  </div>
                  <Badge variant="secondary">uploaded</Badge>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Separator className="my-6" />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Applicant Notes</CardTitle>
          </CardHeader>
          <CardContent>
            {application.student_notes ? (
              <p className="whitespace-pre-wrap">{application.student_notes}</p>
            ) : (
              <p className="text-sm text-muted-foreground">No applicant notes provided.</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Internal Review Notes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {application.internal_notes ? (
              <p className="whitespace-pre-wrap">{application.internal_notes}</p>
            ) : (
              <p className="text-sm text-muted-foreground">No internal notes added.</p>
            )}
            <form method="POST" action={`/admissions/review/${application.id}/notes`} className="space-y-4">
              <div>
                <Label htmlFor="internal_notes">Add Internal Note</Label>
                <Textarea
                  id="internal_notes"
                  name="notes"
                  rows={4}
                  placeholder="Add internal notes for review team..."
                />
              </div>
              <Button type="submit" className="w-full">
                Add Note
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>

      <Separator className="my-6" />

      {canDecide && (
        <form id="decision-form" method="POST" action={`/admissions/applications/${application.id}/decide`} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Review Decision</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              {application.review_notes && (
                <div>
                  <Label>Previous Review Notes</Label>
                  <p className="whitespace-pre-wrap text-sm">{application.review_notes}</p>
                </div>
              )}
              <div>
                <Label>Decision</Label>
                <div className="flex items-center gap-6 mt-2">
                  <label className="flex items-center gap-2">
                    <input type="radio" name="decision" value="approved" defaultChecked />
                    <span>Approve</span>
                  </label>
                  <label className="flex items-center gap-2">
                    <input type="radio" name="decision" value="rejected" />
                    <span>Reject</span>
                  </label>
                </div>
              </div>
              <div>
                <Label htmlFor="review_notes">Review Notes (Optional)</Label>
                <Textarea
                  id="review_notes"
                  name="notes"
                  rows={4}
                  placeholder="Add notes for your decision..."
                />
              </div>
            </CardContent>
          </Card>
        </form>
      )}

      <Separator className="my-6" />

      <Card>
        <CardHeader>
          <CardTitle>Application History</CardTitle>
        </CardHeader>
        <CardContent>
          {(application.events ?? []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No history yet.</p>
          ) : (
            <div className="space-y-4">
              {application.events.map((event) => (
                <div key={event.id} className="border p-4 mb-3 last:mb-0">
                  <div className="flex justify-between items-start mb-2">
                    <div className="flex items-center gap-2">
                      <Badge variant="outline">{event.event_type.replace('_', ' ').toUpperCase()}</Badge>
                      <span className="text-sm text-muted-foreground">
                        {new Date(event.created_at).toLocaleString()}
                      </span>
                    </div>
                    {event.user && (
                      <Badge variant="secondary">by {event.user.name}</Badge>
                    )}
                  </div>
                  {event.notes && (
                    <p className="text-sm whitespace-pre-wrap mt-2">{event.notes}</p>
                  )}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </AppShell>
  );
}
