<?php

declare(strict_types=1);

namespace App\Domain\Admissions\Services;

use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionReviewService
{
    public function __construct(private readonly TenantContext $tenants) {}

    /**
     * Run a read or write inside the school it was asked about.
     *
     * Every method here is handed a school id, so it pins that school rather
     * than assuming a request session: the review queue is also driven from
     * tests and, eventually, commands.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $work
     * @return TResult
     */
    private function inSchool(int $schoolId, Closure $work): mixed
    {
        return $this->tenants->runFor($schoolId, $work);
    }

    /**
     * Get applications ready for review with optional filters
     *
     * @return Collection<int, AdmissionApplication>
     */
    public function getReviewQueue(int $schoolId, array $filters = []): Collection
    {
        $query = AdmissionApplication::where('school_id', $schoolId)
            ->whereIn('status', ['submitted', 'under_review'])
            ->with(['period', 'assignedTo', 'reviewer']);

        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '') {
            if ($filters['assigned_to'] === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $filters['assigned_to']);
            }
        }

        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('student_first_name', 'like', "%{$search}%")
                    ->orWhere('student_last_name', 'like', "%{$search}%")
                    ->orWhere('guardian_email', 'like', "%{$search}%");
            });
        }

        return $this->inSchool($schoolId, fn () => $query->orderBy('submitted_at', 'asc')->get());
    }

    /**
     * Assign an application to a reviewer
     */
    public function assignApplication(AdmissionApplication $application, ?int $reviewerId, User $currentUser): void
    {
        DB::transaction(function () use ($application, $reviewerId, $currentUser) {
            $application->update([
                'assigned_to' => $reviewerId,
            ]);

            $application->events()->create([
                'user_id' => $currentUser->id,
                'event_type' => 'assigned',
                'notes' => $reviewerId
                    ? "Application assigned to reviewer #{$reviewerId}"
                    : 'Application unassigned',
            ]);
        });
    }

    /**
     * Update application priority
     */
    public function updatePriority(AdmissionApplication $application, string $priority, User $currentUser): void
    {
        if (! array_key_exists($priority, $application::PRIORITY_LEVELS)) {
            throw ValidationException::withMessages([
                'priority' => 'Invalid priority level.',
            ]);
        }

        DB::transaction(function () use ($application, $priority, $currentUser) {
            $application->update([
                'priority' => $priority,
            ]);

            $application->events()->create([
                'user_id' => $currentUser->id,
                'event_type' => 'priority_updated',
                'notes' => "Priority changed to {$application::PRIORITY_LEVELS[$priority]}",
            ]);
        });
    }

    /**
     * Add internal notes to an application
     */
    public function addInternalNotes(AdmissionApplication $application, string $notes, User $currentUser): void
    {
        DB::transaction(function () use ($application, $notes, $currentUser) {
            $currentNotes = $application->internal_notes ?? '';
            $newNotes = trim($currentNotes."\n\n[$notes]");

            $application->update([
                'internal_notes' => $newNotes,
            ]);

            $application->events()->create([
                'user_id' => $currentUser->id,
                'event_type' => 'internal_note_added',
                'notes' => 'Internal note added',
            ]);
        });
    }

    /**
     * Get review statistics for a school
     */
    public function getReviewStatistics(int $schoolId): array
    {
        $applications = $this->inSchool($schoolId, fn () => AdmissionApplication::where('school_id', $schoolId)
            ->whereIn('status', ['submitted', 'under_review', 'approved', 'rejected'])
            ->get());

        $total = $applications->count();
        $submitted = $applications->where('status', 'submitted')->count();
        $underReview = $applications->where('status', 'under_review')->count();
        $approved = $applications->where('status', 'approved')->count();
        $rejected = $applications->where('status', 'rejected')->count();

        $avgReviewTime = null;
        $reviewedApps = $applications->whereNotNull('reviewed_at');
        if ($reviewedApps->count() > 0) {
            $totalSeconds = $reviewedApps->sum(fn ($app) => $app->submitted_at ?
                $app->reviewed_at->getTimestamp() - $app->submitted_at->getTimestamp() : 0);
            $avgReviewTime = $totalSeconds / $reviewedApps->count();
        }

        return [
            'total' => $total,
            'submitted' => $submitted,
            'under_review' => $underReview,
            'approved' => $approved,
            'rejected' => $rejected,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            'avg_review_time_hours' => $avgReviewTime ? round($avgReviewTime / 3600, 1) : null,
        ];
    }

    /**
     * Bulk update application status, inside one school.
     *
     * The ids arrive from a form, and not every caller is holding this school's
     * ids: the two queries below used to run over the whole table, so a foreign
     * id was updated to the requested status and then given an event trail on
     * the stranger's application. The school is part of the query now, on both
     * the update and the event lookup.
     */
    public function bulkUpdateStatus(array $applicationIds, string $status, int $schoolId, User $currentUser, ?string $notes = null): int
    {
        if (! in_array($status, AdmissionApplication::STATUSES)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid application status.',
            ]);
        }

        return $this->inSchool($schoolId, fn () => DB::transaction(function () use ($applicationIds, $status, $schoolId, $currentUser, $notes) {
            $updated = AdmissionApplication::where('school_id', $schoolId)
                ->whereIn('id', $applicationIds)
                ->whereIn('status', ['submitted', 'under_review']) // Only allow bulk update of reviewable apps
                ->update([
                    'status' => $status,
                    'reviewed_by' => $status === 'approved' || $status === 'rejected' ? $currentUser->id : null,
                    'reviewed_at' => $status === 'approved' || $status === 'rejected' ? now() : null,
                    'review_notes' => $status === 'approved' || $status === 'rejected' ? $notes : null,
                ]);

            // Create events for each updated application - fetch all applications in one query to avoid N+1
            $applications = AdmissionApplication::where('school_id', $schoolId)
                ->whereIn('id', $applicationIds)
                ->whereIn('status', ['submitted', 'under_review'])
                ->get();

            foreach ($applications as $application) {
                $application->events()->create([
                    'user_id' => $currentUser->id,
                    'event_type' => "bulk_{$status}",
                    'notes' => $notes ?? "Bulk updated to {$status}",
                ]);
            }

            return $updated;
        }));
    }
}
