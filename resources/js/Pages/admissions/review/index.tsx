import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { EmptyState } from '@/components/ui/empty-state';
import { Table, TableHeader, TableBody, TableRow, TableCell } from '@/components/ui/table';
import { Search, Users, Edit, AlertTriangle } from 'lucide-react';
import { Link, useForm, router } from '@inertiajs/react';
import { useState, type ChangeEvent } from 'react';
import { Dialog, DialogContent } from '@/components/ui/dialog';

interface Application {
  id: number;
  reference: string;
  status: string;
  priority: string;
  student_name: string;
  guardian_email: string;
  grade_applying?: string;
  submitted_at?: string;
  assigned_to: { id: number; name: string } | null;
  reviewer: { id: number; name: string } | null;
  internal_notes?: string;
}

interface Statistics {
  total: number;
  submitted: number;
  under_review: number;
  approved: number;
  rejected: number;
  approval_rate: number;
  avg_review_time_hours?: number;
}

interface Reviewers {
  id: number;
  name: string;
}

export default function AdmissionsReviewIndex({
  applications,
  filters,
  reviewers,
  statistics,
  priorityLevels,
}: {
  applications: Application[];
  filters: {
    status?: string;
    assigned_to?: string;
    priority?: string;
    search?: string;
  };
  reviewers: Reviewers[];
  statistics: Statistics;
  priorityLevels: Record<string, string>;
}) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [priorityFilter, setPriorityFilter] = useState(filters.priority ?? '');
  const [selectedApplications, setSelectedApplications] = useState<number[]>([]);
  const [showAssignDialog, setShowAssignDialog] = useState(false);
  const [applicationToAssign, setApplicationToAssign] = useState<Application | null>(null);

  const { data: assignData, post: assignPost, setData } = useForm({
    reviewer_id: '',
  });

  const applyFilters = () => {
    router.get('/admissions/review', {
      status: filters.status || undefined,
      assigned_to: filters.assigned_to || undefined,
      priority: filters.priority || undefined,
      search: filters.search || undefined,
    }, { preserveState: true });
  };

  const handleSelectAll = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      setSelectedApplications(applications.map(app => app.id));
    } else {
      setSelectedApplications([]);
    }
  };

  const handleSelectApplication = (id: number, checked: boolean) => {
    setSelectedApplications(prev => checked ? [...prev, id] : prev.filter(appId => appId !== id));
  };

  const isAllSelected = selectedApplications.length === applications.length && applications.length > 0;

  const handleBulkAction = async (action: string) => {
    if (selectedApplications.length === 0) {
      alert('Please select at least one application');
      return;
    }

    const actionLabels: Record<string, string> = {
      approve: 'approve',
      reject: 'reject',
      set_under_review: 'set to under review',
    };

    if (!window.confirm(`Are you sure you want to ${actionLabels[action]} the selected applications?`)) {
      return;
    }

    await router.post('/admissions/review/bulk-update', {
      application_ids: selectedApplications,
      status: action === 'approve' ? 'approved' : action === 'reject' ? 'rejected' : 'under_review',
      notes: `Bulk ${actionLabels[action]}`,
    }, {
      onSuccess: () => {
        setSelectedApplications([]);
        applyFilters();
      },
      onError: () => {
        alert('Failed to update applications');
      }
    });
  };

  const statusVariant: Record<string, string> = {
    draft: 'secondary',
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

  return (
    <AppShell
      title="Admissions Review"
      breadcrumbs={[
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Admissions', href: '/admissions/applications' },
        { label: 'Review' },
      ]}
    >
      <PageHeader
        title="Admissions Review Queue"
        description="Review and process admission applications"
        actions={
          <div className="flex flex-wrap items-center gap-4">
            <Button variant="outline" onClick={() => setShowAssignDialog(true)}>
              <Users className="mr-2 h-4 w-4" /> Assign Applications
            </Button>
            <Button onClick={() => handleBulkAction('approve')} disabled={selectedApplications.length === 0}>
              Approve Selected
            </Button>
            <Button variant="outline" onClick={() => handleBulkAction('reject')} disabled={selectedApplications.length === 0}>
              Reject Selected
            </Button>
            <Button variant="outline" onClick={() => handleBulkAction('set_under_review')} disabled={selectedApplications.length === 0}>
              <AlertTriangle className="mr-2 h-4 w-4" /> Set to Review
            </Button>
          </div>
        }
      />

      <Dialog open={showAssignDialog} onOpenChange={setShowAssignDialog}>
        <DialogContent className="w-full max-w-md">
          <div className="p-6">
            <h2 className="text-lg font-semibold mb-2">Assign Applications to Reviewer</h2>
            <p className="text-sm text-muted-foreground mb-4">
              Select a reviewer to assign the selected {selectedApplications.length} application(s)
            </p>
            <form onSubmit={(e) => {
              e.preventDefault();
              assignPost(`/admissions/review/${applicationToAssign?.id}/assign`, {
                onSuccess: () => {
                  setShowAssignDialog(false);
                  setSelectedApplications([]);
                  applyFilters();
                },
                onError: () => {
                  alert('Failed to assign application');
                }
              });
            }}>
              <div className="space-y-4">
                <div>
                  <label htmlFor="reviewer_id" className="label mb-1.5 block">Reviewer</label>
                  <Select
                    id="reviewer_id"
                    value={assignData.reviewer_id}
                    onChange={(e) => setData('reviewer_id', e.target.value)}
                  >
                    <option value="">Select a reviewer</option>
                    {reviewers.map(reviewer => (
                      <option key={reviewer.id} value={reviewer.id.toString()}>
                        {reviewer.name}
                      </option>
                    ))}
                  </Select>
                </div>
                <div className="flex justify-end gap-3">
                  <Button type="button" variant="outline" onClick={() => setShowAssignDialog(false)}>
                    Cancel
                  </Button>
                  <Button type="submit" disabled={!assignData.reviewer_id}>
                    Assign
                  </Button>
                </div>
              </div>
            </form>
          </div>
        </DialogContent>
      </Dialog>

      {/* Statistics Cards */}
      <div className="grid gap-4 mb-6 lg:grid-cols-4">
        <div className="bg-card p-4 rounded-lg border">
          <h3 className="text-xs font-medium text-muted-foreground mb-2">Total Applications</h3>
          <p className="text-2xl font-bold">{statistics.total}</p>
        </div>
        <div className="bg-card p-4 rounded-lg border">
          <h3 className="text-xs font-medium text-muted-foreground mb-2">Submitted</h3>
          <p className="text-2xl font-bold text-info">{statistics.submitted}</p>
        </div>
        <div className="bg-card p-4 rounded-lg border">
          <h3 className="text-xs font-medium text-muted-foreground mb-2">Under Review</h3>
          <p className="text-2xl font-bold text-warning">{statistics.under_review}</p>
        </div>
        <div className="bg-card p-4 rounded-lg border">
          <h3 className="text-xs font-medium text-muted-foreground mb-2">Approval Rate</h3>
          <p className="text-2xl font-bold">{statistics.approval_rate}%</p>
          {statistics.avg_review_time_hours !== undefined && (
            <p className="text-xs text-muted-foreground mt-1">
              Avg. review: {statistics.avg_review_time_hours}h
            </p>
          )}
        </div>
      </div>

      {/* Filters */}
      <div className="mb-6">
        <div className="flex flex-wrap items-center gap-4 border-b p-4">
          <div className="flex-1 min-w-[200px]">
            <form onSubmit={(e) => { e.preventDefault(); applyFilters(); }}>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search applications..."
                  className="pl-9"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </form>
          </div>
          <div className="flex flex-wrap gap-2">
            {Object.entries(priorityLevels).map(([value, label]) => (
              <button
                key={value}
                onClick={() => setPriorityFilter(value)}
                className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
                  priorityFilter === value ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:text-foreground'
                }`}
              >
                {label}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Applications Table */}
      {applications.length > 0 ? (
        <Table className="w-full">
          <TableHeader>
            <TableRow className="hover:bg-muted/50">
              <TableCell className="w-4">
                <Checkbox
                  checked={isAllSelected}
                  onChange={handleSelectAll}
                  className="h-4 w-4"
                />
              </TableCell>
              <TableCell>Reference</TableCell>
              <TableCell>Student</TableCell>
              <TableCell>Grade</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Priority</TableCell>
              <TableCell>Assigned To</TableCell>
              <TableCell>Submitted</TableCell>
              <TableCell className="text-right">Actions</TableCell>
            </TableRow>
          </TableHeader>
          <TableBody>
            {applications.map((application) => (
              <TableRow key={application.id} className="border-b hover:bg-muted/50 last:border-0">
                <TableCell className="w-4">
                  <Checkbox
                    checked={selectedApplications.includes(application.id)}
                    onChange={(e) => handleSelectApplication(application.id, e.target.checked)}
                    className="h-4 w-4"
                  />
                </TableCell>
                <TableCell>
                  <Link href={`/admissions/review/${application.id}`} className="text-primary hover:underline">
                    {application.reference}
                  </Link>
                </TableCell>
                <TableCell>{application.student_name}</TableCell>
                <TableCell>{application.grade_applying ?? '—'}</TableCell>
                <TableCell>
                  <Badge variant={statusVariant[application.status] as any}>
                    {application.status.replace('_', ' ')}
                  </Badge>
                </TableCell>
                <TableCell>
                  <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                    priorityVariant[application.priority] === 'destructive' ? 'bg-red-100 text-red-800' :
                    priorityVariant[application.priority] === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                    priorityVariant[application.priority] === 'success' ? 'bg-green-100 text-green-800' :
                    'bg-blue-100 text-blue-800'
                  }`}>
                    {application.priority}
                  </span>
                </TableCell>
                <TableCell>
                  {application.assigned_to ? (
                    <span className="text-sm">{application.assigned_to.name}</span>
                  ) : (
                    <span className="text-xs text-muted-foreground">Unassigned</span>
                  )}
                </TableCell>
                <TableCell>
                  {application.submitted_at ? (
                    <span className="text-sm">{new Date(application.submitted_at).toLocaleDateString()}</span>
                  ) : (
                    '—'
                  )}
                </TableCell>
                <TableCell className="text-right">
                  <div className="flex justify-end gap-2">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`/admissions/review/${application.id}`}>
                        <Edit className="h-3 w-3" /> Review
                      </Link>
                    </Button>
                    {!application.assigned_to && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          setApplicationToAssign(application);
                          setShowAssignDialog(true);
                        }}
                      >
                        <Users className="h-3 w-3" /> Assign
                      </Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      ) : (
        <EmptyState
          title="No applications match your filters"
          description="Try adjusting your filters to see applications that need review."
        />
      )}
    </AppShell>
  );
}
