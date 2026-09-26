<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admissions;

use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Services\AdmissionReviewService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(private readonly AdmissionReviewService $reviewService)
    {
        $this->middleware('can:manage-admissions');
    }

    /**
     * Show the admissions review dashboard/queue
     */
    public function index(Request $request): Response
    {
        $schoolId = $request->session()->get('school_id');
        $request->user();

        $filters = $request->only(['status', 'assigned_to', 'priority', 'search']);
        $applications = $this->reviewService->getReviewQueue($schoolId, $filters);

        $reviewers = $this->reviewers();

        $statistics = $this->reviewService->getReviewStatistics($schoolId);

        return Inertia::render('admissions/review/index', [
            'applications' => $applications->map(fn ($app) => [
                'id' => $app->id,
                'reference' => $app->reference,
                'status' => $app->status,
                'priority' => $app->priority ?? 'medium',
                'student_name' => trim($app->student_first_name.' '.$app->student_last_name),
                'guardian_email' => $app->guardian_email,
                'grade_applying' => $app->grade_applying,
                'submitted_at' => $app->submitted_at,
                'assigned_to' => $app->assignedTo
                    ? [
                        'id' => $app->assignedTo->id,
                        'name' => $app->assignedTo->name,
                    ]
                    : null,
                'reviewer' => $app->reviewer
                    ? [
                        'id' => $app->reviewer->id,
                        'name' => $app->reviewer->name,
                    ]
                    : null,
                'internal_notes' => $app->internal_notes,
            ]),
            'filters' => $filters,
            'reviewers' => $reviewers,
            'statistics' => $statistics,
            'priorityLevels' => [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'urgent' => 'Urgent',
            ],
        ]);
    }

    /**
     * Assign an application to a reviewer
     */
    public function assign(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $this->authorize('manage-admissions');

        $request->validate([
            'reviewer_id' => 'nullable|integer|exists:users,id',
        ]);

        $this->reviewService->assignApplication(
            $application,
            $request->input('reviewer_id'),
            $request->user()
        );

        return back()->with('success', 'Application assigned successfully.');
    }

    /**
     * Update application priority
     */
    public function updatePriority(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $this->authorize('manage-admissions');

        $request->validate([
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);

        $this->reviewService->updatePriority(
            $application,
            $request->input('priority'),
            $request->user()
        );

        return back()->with('success', 'Priority updated successfully.');
    }

    /**
     * Add internal notes to an application
     */
    public function addInternalNotes(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $this->authorize('manage-admissions');

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $this->reviewService->addInternalNotes(
            $application,
            $request->input('notes'),
            $request->user()
        );

        return back()->with('success', 'Internal note added successfully.');
    }

    /**
     * Bulk update application status
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $this->authorize('manage-admissions');

        $request->validate([
            'application_ids' => ['required', 'array'],
            'application_ids.*' => ['integer', 'exists:admission_applications,id'],
            'status' => ['required', 'in:approved,rejected,under_review'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $count = $this->reviewService->bulkUpdateStatus(
            $request->input('application_ids'),
            $request->input('status'),
            $request->user(),
            $request->input('notes')
        );

        return back()->with('success', "{$count} applications updated successfully.");
    }

    /**
     * Get application details for review modal
     */
    public function showForReview(AdmissionApplication $application): Response
    {
        $reviewers = $this->reviewers();

        $this->authorize('manage-admissions');

        return Inertia::render('admissions/review/show', [
            'application' => [
                'id' => $application->id,
                'reference' => $application->reference,
                'status' => $application->status,
                'priority' => $application->priority ?? 'medium',
                'student_first_name' => $application->student_first_name,
                'student_last_name' => $application->student_last_name,
                'student_date_of_birth' => $application->student_date_of_birth,
                'student_gender' => $application->student_gender,
                'student_nationality' => $application->student_nationality,
                'grade_applying' => $application->grade_applying,
                'guardian_first_name' => $application->guardian_first_name,
                'guardian_last_name' => $application->guardian_last_name,
                'guardian_email' => $application->guardian_email,
                'guardian_phone' => $application->guardian_phone,
                'guardian_relationship' => $application->guardian_relationship,
                'guardian_national_id' => $application->guardian_national_id,
                'previous_school_name' => $application->previous_school_name,
                'previous_school_last_grade' => $application->previous_school_last_grade,
                'previous_school_year_completed' => $application->previous_school_year_completed,
                'student_notes' => $application->student_notes,
                'documents' => $application->documents,
                'internal_notes' => $application->internal_notes,
                'review_notes' => $application->review_notes,
                'submitted_at' => $application->submitted_at,
                'created_at' => $application->created_at,
                'updated_at' => $application->updated_at,
                'events' => $application->events->map(fn ($event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'notes' => $event->notes,
                    'created_at' => $event->created_at,
                    'user' => $event->user
                        ? [
                            'id' => $event->user->id,
                            'name' => $event->user->name,
                        ]
                        : null,
                ]),
            ],
            'reviewers' => $reviewers,
        ]);
    }

    /**
     * Staff who may be assigned applications.
     *
     * Resolved through the Spatie role → permission relations; the previous
     * `rolePermissions`/`permission` relations do not exist and the selected
     * `first_name`/`last_name` columns do not exist on users either.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reviewers(): array
    {
        return User::whereHas('roles.permissions', function ($query) {
            $query->where('name', 'manage-admissions');
        })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }
}
