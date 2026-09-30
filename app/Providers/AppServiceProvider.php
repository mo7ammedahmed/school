<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\GradingScale;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Policies\AcademicYearPolicy;
use App\Domain\Academics\Policies\EnrollmentPolicy;
use App\Domain\Academics\Policies\GradeLevelPolicy;
use App\Domain\Academics\Policies\GradingCategoryPolicy;
use App\Domain\Academics\Policies\GradingScalePolicy;
use App\Domain\Academics\Policies\OfferingPolicy;
use App\Domain\Academics\Policies\SectionPolicy;
use App\Domain\Academics\Policies\SemesterPolicy;
use App\Domain\Academics\Policies\SubjectPolicy;
use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\AssessmentScore;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Assessment\Policies\AssessmentPolicy;
use App\Domain\Assessment\Policies\AssessmentScorePolicy;
use App\Domain\Assessment\Policies\ExamPolicy;
use App\Domain\Assessment\Policies\ExamResultPolicy;
use App\Domain\Assessment\Policies\ReportCardPolicy;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\Attendance\Policies\AttendanceRecordPolicy;
use App\Domain\Attendance\Policies\AttendanceSessionPolicy;
use App\Domain\Communication\Models\Announcement;
use App\Domain\Communication\Models\Conversation;
use App\Domain\Communication\Models\Message;
use App\Domain\Communication\Models\Notification;
use App\Domain\Communication\Policies\AnnouncementPolicy;
use App\Domain\Communication\Policies\ConversationPolicy;
use App\Domain\Communication\Policies\MessagePolicy;
use App\Domain\Communication\Policies\NotificationPolicy;
use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Compliance\Policies\AuditLogPolicy;
use App\Domain\Content\Models\ContactLead;
use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Models\Event;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\News;
use App\Domain\Content\Models\StaffProfile;
use App\Domain\Content\Policies\ContactLeadPolicy;
use App\Domain\Content\Policies\ContentPagePolicy;
use App\Domain\Content\Policies\EventPolicy;
use App\Domain\Content\Policies\FaqPolicy;
use App\Domain\Content\Policies\NewsPolicy;
use App\Domain\Content\Policies\StaffProfilePolicy;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Documents\Policies\DocumentCategoryPolicy;
use App\Domain\Documents\Policies\DocumentPolicy;
use App\Domain\Finance\Models\Discount;
use App\Domain\Finance\Models\FeeAssignment;
use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Finance\Models\FeeType;
use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\InvoiceLine;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentAllocation;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Models\WebhookEvent;
use App\Domain\Finance\Policies\DiscountPolicy;
use App\Domain\Finance\Policies\FeeAssignmentPolicy;
use App\Domain\Finance\Policies\FeeStructurePolicy;
use App\Domain\Finance\Policies\FeeTypePolicy;
use App\Domain\Finance\Policies\GatewayTransactionPolicy;
use App\Domain\Finance\Policies\InvoiceLinePolicy;
use App\Domain\Finance\Policies\InvoicePolicy;
use App\Domain\Finance\Policies\PaymentAllocationPolicy;
use App\Domain\Finance\Policies\PaymentPolicy;
use App\Domain\Finance\Policies\RefundPolicy;
use App\Domain\Finance\Policies\WebhookEventPolicy;
use App\Domain\Finance\Webhooks\MoyasarWebhookVerifier;
use App\Domain\Finance\Webhooks\WebhookVerifierRegistry;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Identity\Policies\UserMembershipPolicy;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Material;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Learning\Models\QuizAttempt;
use App\Domain\Learning\Models\Submission;
use App\Domain\Learning\Policies\AssignmentPolicy;
use App\Domain\Learning\Policies\MaterialPolicy;
use App\Domain\Learning\Policies\QuizAttemptPolicy;
use App\Domain\Learning\Policies\QuizPolicy;
use App\Domain\Learning\Policies\SubmissionPolicy;
use App\Domain\Localization\Observers\FillsMissingTranslations;
use App\Domain\Localization\Services\ArabicShaper;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\People\Policies\StudentPolicy;
use App\Domain\People\Policies\TeacherPolicy;
use App\Domain\Scheduling\Models\CalendarDay;
use App\Domain\Scheduling\Models\Period;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Scheduling\Policies\CalendarDayPolicy;
use App\Domain\Scheduling\Policies\PeriodPolicy;
use App\Domain\Scheduling\Policies\RoomPolicy;
use App\Domain\Scheduling\Policies\TimetableEntryPolicy;
use App\Http\Middleware\ApplySiteMetadata;
use App\Models\Classroom;
use App\Models\Enrollment as AppEnrollment;
use App\Models\GradeLevel as AppGradeLevel;
use App\Models\Guardian as AppGuardian;
use App\Models\Room as AppRoom;
use App\Models\Section as AppSection;
use App\Models\Student as AppStudent;
use App\Models\Subject as AppSubject;
use App\Models\Teacher as AppTeacher;
use App\Models\Timetable as AppTimetable;
use App\Models\User;
use App\Policies\ClassroomPolicy;
use App\Policies\GuardianPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\ErrorPages;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The gateways whose deliveries can be authenticated. A gateway absent
        // from this list is refused by the webhook endpoint, because there is
        // nothing to verify it with.
        $this->app->singleton(WebhookVerifierRegistry::class, fn (): WebhookVerifierRegistry => new WebhookVerifierRegistry([
            new MoyasarWebhookVerifier,
        ]));
    }

    public function boot(): void
    {
        // Super Admin gate. Platform support access must additionally go through
        // the audited support-access flow; this gate only bypasses per-school
        // policy checks for platform operators.
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $this->registerPolicies();
        $this->registerHeadDefaults();
        $this->registerErrorMetadata();
        $this->registerArabicShaper();

        FillsMissingTranslations::register();
    }

    /**
     * Maps every guarded model to the policy that answers for it.
     *
     * This used to live in an `AuthServiceProvider` that was never added to
     * `bootstrap/providers.php`, so the map it declared had never run: every
     * policy in the application was being found by Laravel's naming
     * convention instead. That works for most of the domain models, because
     * `App\Domain\Finance\Models\Invoice` guesses
     * `App\Domain\Finance\Policies\InvoicePolicy` — but it silently failed
     * wherever the model and policy names diverged. `TeacherProfile` was the
     * live example: `TeacherController` binds it, and it resolved to no policy
     * at all. The map is here now, explicit, so nothing depends on a guess.
     *
     * The `App\Models\*` rows are deliberate aliases, not duplicates. Those
     * classes are thin subclasses of their domain model, and controllers type
     * hint the `App\Models` name, so `Gate` looks the policy up under that
     * exact class and the convention would otherwise find a second, parallel
     * policy class. The subclass relationship means one policy serves both
     * names.
     *
     * `App\Domain\People\Models\Guardian` is mapped explicitly even though
     * nothing binds it today. Laravel's policy guesser walks up the namespace
     * until it finds a class that exists, so it lands on
     * `App\Policies\GuardianPolicy` for the domain model regardless — which is
     * why that policy hints the domain `Guardian` rather than the subclass.
     *
     * @see GuardianPolicy
     * @see UserPolicy
     */
    private function registerPolicies(): void
    {
        $map = [
            // People
            AppStudent::class => StudentPolicy::class,
            Student::class => StudentPolicy::class,
            AppTeacher::class => TeacherPolicy::class,
            TeacherProfile::class => TeacherPolicy::class,
            AppGuardian::class => GuardianPolicy::class,
            Guardian::class => GuardianPolicy::class,
            User::class => UserPolicy::class,
            UserMembership::class => UserMembershipPolicy::class,

            // Academics
            AppGradeLevel::class => GradeLevelPolicy::class,
            AppSection::class => SectionPolicy::class,
            AppSubject::class => SubjectPolicy::class,
            AppEnrollment::class => EnrollmentPolicy::class,
            AcademicYear::class => AcademicYearPolicy::class,
            Semester::class => SemesterPolicy::class,
            Offering::class => OfferingPolicy::class,
            GradingScale::class => GradingScalePolicy::class,
            GradingCategory::class => GradingCategoryPolicy::class,

            // Scheduling
            AppRoom::class => RoomPolicy::class,
            Classroom::class => ClassroomPolicy::class,
            AppTimetable::class => TimetableEntryPolicy::class,
            TimetableEntry::class => TimetableEntryPolicy::class,
            Period::class => PeriodPolicy::class,
            CalendarDay::class => CalendarDayPolicy::class,

            // Attendance and assessment
            AttendanceRecord::class => AttendanceRecordPolicy::class,
            AttendanceSession::class => AttendanceSessionPolicy::class,
            Assessment::class => AssessmentPolicy::class,
            AssessmentScore::class => AssessmentScorePolicy::class,
            Exam::class => ExamPolicy::class,
            ExamResult::class => ExamResultPolicy::class,
            ReportCard::class => ReportCardPolicy::class,

            // Learning
            Material::class => MaterialPolicy::class,
            Assignment::class => AssignmentPolicy::class,
            Submission::class => SubmissionPolicy::class,
            Quiz::class => QuizPolicy::class,
            QuizAttempt::class => QuizAttemptPolicy::class,

            // Finance
            Invoice::class => InvoicePolicy::class,
            InvoiceLine::class => InvoiceLinePolicy::class,
            Payment::class => PaymentPolicy::class,
            PaymentAllocation::class => PaymentAllocationPolicy::class,
            Refund::class => RefundPolicy::class,
            FeeType::class => FeeTypePolicy::class,
            FeeStructure::class => FeeStructurePolicy::class,
            FeeAssignment::class => FeeAssignmentPolicy::class,
            Discount::class => DiscountPolicy::class,
            GatewayTransaction::class => GatewayTransactionPolicy::class,
            WebhookEvent::class => WebhookEventPolicy::class,

            // Documents and communication
            Document::class => DocumentPolicy::class,
            DocumentCategory::class => DocumentCategoryPolicy::class,
            Announcement::class => AnnouncementPolicy::class,
            Conversation::class => ConversationPolicy::class,
            Message::class => MessagePolicy::class,
            Notification::class => NotificationPolicy::class,

            // Content
            ContentPage::class => ContentPagePolicy::class,
            News::class => NewsPolicy::class,
            Event::class => EventPolicy::class,
            Faq::class => FaqPolicy::class,
            StaffProfile::class => StaffProfilePolicy::class,
            ContactLead::class => ContactLeadPolicy::class,

            // Compliance
            AuditLog::class => AuditLogPolicy::class,
        ];

        foreach ($map as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * dompdf cannot join Arabic letters, so PDF views hand their text through
     * {@see ArabicShaper} with `@shaped($value)`.
     */
    private function registerArabicShaper(): void
    {
        Blade::directive('shaped', fn (string $expression): string => "<?php echo e(\\App\\Domain\\Localization\\Services\\ArabicShaper::forLocale({$expression})); ?>");
    }

    /**
     * The floor under every page's metadata. These values are only used when
     * nothing more specific exists: the school's own identity is applied per
     * request by {@see ApplySiteMetadata}.
     */
    private function registerHeadDefaults(): void
    {
        Head::defaults(fn (HeadBuilder $head) => $head
            // No suffix here: the school's name is the suffix, and it is only
            // known once the request has been resolved.
            ->title(config('app.name'))
            ->description('School management for admissions, academics, attendance and finance.')
            ->canonical()
            ->colorScheme('light dark')
            ->referrer('strict-origin-when-cross-origin')
            ->searchableByRobots()
            ->preconnect('https://fonts.bunny.net')
        );

    }

    /**
     * Error screens must never be indexed, and should still name themselves.
     */
    private function registerErrorMetadata(): void
    {
        Head::errors(function (ErrorPages $errors): void {
            $errors->defaults(robots: 'noindex, nofollow');

            $errors->status(403, title: 'Not Allowed');
            $errors->status(404, title: 'Page Not Found', description: 'The page you are looking for has moved or never existed.');
            $errors->status(419, title: 'Session Expired', description: 'Please reload the page and try again.');
            $errors->status(500, title: 'Something Went Wrong');
            $errors->status(503, title: 'Temporarily Unavailable');
        });
    }
}
