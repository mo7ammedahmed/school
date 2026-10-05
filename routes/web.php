<?php

declare(strict_types=1);

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Admissions\ReviewController;
use App\Http\Controllers\AdmissionsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentImportController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SchoolSelectionController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarDayController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\Finance\DiscountController as FinanceDiscountController;
use App\Http\Controllers\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\Finance\RefundController as FinanceRefundController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\Guardian\PortalController as GuardianPortalController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\LiveHlsController;
use App\Http\Controllers\LiveSessionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MediaAuthorizationController;
use App\Http\Controllers\MediaHookController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\Platform\HealthController;
use App\Http\Controllers\Platform\OrganizationController;
use App\Http\Controllers\Platform\SupportAccessController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\AdmissionsController as PublicAdmissionsController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\EventsController as PublicEventsController;
use App\Http\Controllers\Public\FacilitiesController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsController as PublicNewsController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ProgramsController;
use App\Http\Controllers\Public\PublicInvoicePaymentController;
use App\Http\Controllers\Public\TeachersController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SaveTranslatedFieldController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\Settings\AcademicSettingsController;
use App\Http\Controllers\Settings\AppearanceSettingsController;
use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\EmailSettingsController;
use App\Http\Controllers\Settings\GradingSettingsController;
use App\Http\Controllers\Settings\InterfaceTranslationController;
use App\Http\Controllers\Settings\LocalizationSettingsController;
use App\Http\Controllers\Settings\NavigationSettingsController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\PaymentSettingsController;
use App\Http\Controllers\Settings\PreferenceController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SchoolSettingsController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SecuritySettingsController;
use App\Http\Controllers\Settings\SmsSettingsController;
use App\Http\Controllers\Settings\ThemeSettingsController;
use App\Http\Controllers\Settings\TranslationSettingsController;
use App\Http\Controllers\Student\PortalController as StudentPortalController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\UiCopyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Interface language is a device-level choice, so it is available to guests too.
Route::post('/locale', LocaleController::class)->name('locale');

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('public.about');
Route::get('/programs', [ProgramsController::class, 'index'])->name('public.programs');
Route::get('/programs/{program}', [ProgramsController::class, 'show'])->name('public.programs.show');
Route::get('/admissions', [PublicAdmissionsController::class, 'index'])->name('public.admissions');
Route::get('/facilities', [FacilitiesController::class, 'index'])->name('public.facilities');
// The teaching staff live at /faculty, not /teachers: the dashboard's teacher
// resource claims /teachers, and a route registered later replaces an earlier one
// with the same method and URI — so the public page was never reachable and the
// site's own nav sent visitors to the login screen. The route names stay
// `public.teachers` because that is what names the page in <head>.
Route::get('/faculty', [TeachersController::class, 'index'])->name('public.teachers');
Route::get('/faculty/{teacher}', [TeachersController::class, 'show'])->name('public.teachers.show');
Route::get('/news', [PublicNewsController::class, 'index'])->name('news.index');
Route::get('/news/{post}', [PublicNewsController::class, 'show'])->name('news.show');
Route::get('/events', [PublicEventsController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [PublicEventsController::class, 'show'])->name('events.show');
Route::get('/contact', [ContactController::class, 'index'])->name('public.contact');
// Rate limited: this endpoint sends mail to the school's own inbox, and anyone
// on the internet can post to it.
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('public.contact.submit');
Route::get('/faq', [FaqController::class, 'index'])->name('public.faq');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('public.page');

// Apply / Admissions journey. Rate limited: anonymous visitors can create
// application rows and store uploads through it.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/apply', [PublicAdmissionsController::class, 'apply'])->name('apply');
    Route::get('/apply/start', [PublicAdmissionsController::class, 'start'])->name('apply.start');
    Route::post('/apply/start', [PublicAdmissionsController::class, 'storeStart']);
    Route::get('/apply/guardian', [PublicAdmissionsController::class, 'guardian'])->name('apply.guardian');
    Route::post('/apply/guardian', [PublicAdmissionsController::class, 'storeGuardian']);
    Route::get('/apply/student', [PublicAdmissionsController::class, 'student'])->name('apply.student');
    Route::post('/apply/student', [PublicAdmissionsController::class, 'storeStudent']);
    Route::get('/apply/previous-school', [PublicAdmissionsController::class, 'previousSchool'])->name('apply.previous-school');
    Route::post('/apply/previous-school', [PublicAdmissionsController::class, 'storePreviousSchool']);
    Route::get('/apply/documents', [PublicAdmissionsController::class, 'documents'])->name('apply.documents');
    Route::post('/apply/documents', [PublicAdmissionsController::class, 'storeDocuments']);
    Route::get('/apply/review', [PublicAdmissionsController::class, 'review'])->name('apply.review');
    Route::post('/apply/submit', [PublicAdmissionsController::class, 'submit'])->name('apply.submit');
    Route::get('/apply/submitted', [PublicAdmissionsController::class, 'submitted'])->name('apply.submitted');
});

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password/{token}', [ResetPasswordController::class, 'store']);
    Route::get('/two-factor-challenge', [TwoFactorAuthenticationController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorAuthenticationController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    // The verification notice is for a user who is signed in and unverified, so
    // it belongs here: inside the guest group the `guest` middleware bounced the
    // one visitor the page is for to the dashboard.
    Route::get('/verify-email', [VerifyEmailController::class, 'create'])->name('verification.notice');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/select-school', [SchoolSelectionController::class, 'index'])->name('school.select');
    Route::post('/select-school', [SchoolSelectionController::class, 'select']);
});

// Onboarding
//
// A walkthrough, not a write path: each step renders a form and its Shepherd
// tour explains what the real screen will ask for, but nothing here provisions
// anything. The nine POST routes that used to sit under it named `store*`
// methods that were never written, so every submit was a 500 — and no screen
// linked to the wizard at all. Provisioning has a real, tested path: Settings
// → Schools (POST /schools), which creates the membership too.
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    Route::get('/school-information', [OnboardingController::class, 'schoolInformation'])->name('school-information');
    Route::get('/create-school', [OnboardingController::class, 'createSchool'])->name('create-school');
    Route::get('/academic-year', [OnboardingController::class, 'academicYear'])->name('academic-year');
    Route::get('/grades', [OnboardingController::class, 'grades'])->name('grades');
    Route::get('/subjects', [OnboardingController::class, 'subjects'])->name('subjects');
    Route::get('/teachers', [OnboardingController::class, 'teachers'])->name('teachers');
    Route::get('/students', [OnboardingController::class, 'students'])->name('students');
    Route::get('/fee-structure', [OnboardingController::class, 'feeStructure'])->name('fee-structure');
    Route::get('/payment-gateway', [OnboardingController::class, 'paymentGateway'])->name('payment-gateway');
    Route::get('/finish', [OnboardingController::class, 'finish'])->name('finish');
});

// Authenticated application routes
// Translates a single field for the bilingual inputs. Any signed-in author can
// use it; it spends provider credits so it is rate limited per user.
Route::middleware(['auth', 'school.context'])
    ->post('/translate', TranslateController::class)
    ->name('translate');

Route::middleware(['auth', 'school.context'])
    ->post('/translate/save', SaveTranslatedFieldController::class)
    ->name('translate.save');

Route::middleware(['auth', 'school.context'])
    ->get('/live/{liveSession}/hls/{file}', LiveHlsController::class)
    ->where('file', '[A-Za-z0-9_.-]+')->name('live.hls');

// Translates the dashboard's own interface words, so choosing Arabic does not
// leave English headings and buttons around Arabic content. Same rate limit
// reasoning as /translate: it spends the school's provider credits.
Route::middleware(['auth', 'school.context'])
    ->post('/ui/copy', UiCopyController::class)
    ->name('ui.copy');

Route::middleware(['auth', 'school.context'])
    ->get('/ui/copy/version', [UiCopyController::class, 'version'])
    ->name('ui.copy.version');

// The dictionary in one piece. Free to serve — it reads the catalog and never
// calls the provider — so a page that arrived without it can still paint Arabic.
Route::middleware(['auth', 'school.context'])
    ->get('/ui/copy/catalog', [UiCopyController::class, 'catalog'])
    ->name('ui.copy.catalog');

Route::middleware(['auth', 'school.context'])->prefix('')->name('')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view-dashboard')
        ->name('dashboard');

    // Academics
    Route::prefix('academic-years')->name('academic-years.')->middleware('permission:manage-academic-years')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::get('/create', [AcademicYearController::class, 'create'])->name('create');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::get('/{year}', [AcademicYearController::class, 'show'])->name('show');
        Route::get('/{year}/edit', [AcademicYearController::class, 'edit'])->name('edit');
        Route::put('/{year}', [AcademicYearController::class, 'update'])->name('update');
        // No destroy: the controller has no method for it, the screen has no
        // delete action, and deleting a year cascades into its sections.
        Route::get('/{year}/semesters', [SemesterController::class, 'index'])->name('semesters.index');
    });

    Route::resource('semesters', SemesterController::class)->except(['index'])->middleware('permission:manage-semesters');
    Route::resource('grade-levels', GradeLevelController::class)->middleware('permission:manage-grade-levels');
    Route::resource('sections', SectionController::class)->middleware('permission:manage-sections');
    Route::resource('subjects', SubjectController::class)->middleware('permission:manage-subjects');
    // No offerings screen: the route named a method nobody wrote and no page
    // links to it. The Offering model is used by the timetable, not by a
    // subject drill-down.

    // People
    Route::resource('students', StudentController::class)->middleware('permission:manage-students');
    Route::resource('guardians', GuardianController::class)->middleware('permission:manage-guardians');
    // The staff screens have no delete and no per-teacher schedule: the teacher
    // list links to show/edit only, and a teacher's timetable is read from the
    // timetable screens.
    Route::resource('teachers', TeacherController::class)
        ->except(['destroy'])
        ->middleware('permission:manage-teachers');

    // Scheduling
    Route::resource('rooms', RoomController::class)->middleware('permission:manage-rooms');
    Route::resource('periods', PeriodController::class)->except(['create', 'show', 'edit'])->middleware('permission:manage-periods');

    // Registered before the resource so /timetable/grid is not swallowed by /timetable/{timetable}.
    Route::middleware('permission:manage-timetable-entries')->group(function () {
        Route::get('/timetable/grid', [TimetableController::class, 'grid'])->name('timetable.grid');
        Route::get('/timetable/calendar', [TimetableController::class, 'calendar'])->name('timetable.calendar');
        Route::get('/timetable/list', [TimetableController::class, 'list'])->name('timetable.list');
        Route::get('/timetable/print', [TimetableController::class, 'print'])->name('timetable.print');
        Route::get('/timetable/conflicts', [TimetableController::class, 'conflicts'])->name('timetable.conflicts');
        Route::get('/timetable/export/pdf', [TimetableController::class, 'exportPdf'])->name('timetable.export.pdf');
        Route::get('/timetable/export/ics', [TimetableController::class, 'exportIcs'])->name('timetable.export.ics');
        Route::get('/timetable/teacher/{teacher}', [TimetableController::class, 'teacher'])->name('timetable.teacher');
        Route::get('/timetable/class/{section}', [TimetableController::class, 'section'])->name('timetable.section');
        Route::post('/timetable/{timetable}/publish', [TimetableController::class, 'publish'])->name('timetable.publish');
        Route::post('/timetable/{timetable}/unpublish', [TimetableController::class, 'unpublish'])->name('timetable.unpublish');
        Route::resource('timetable', TimetableController::class);
    });
    Route::get('/my-schedule', [TimetableController::class, 'mySchedule'])
        ->middleware('permission:view-own-schedule')
        ->name('my-schedule');

    // Academic calendar
    Route::middleware('permission:manage-calendar')->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/feed', [CalendarController::class, 'feed'])->name('calendar.feed');
        Route::get('/calendar/export.ics', [CalendarController::class, 'download'])->name('calendar.export');
        Route::resource('calendar-days', CalendarDayController::class)->only(['store', 'update', 'destroy']);
    });

    // Attendance
    Route::resource('attendance-sessions', AttendanceSessionController::class)
        ->parameters(['attendance-sessions' => 'session'])
        ->middleware('permission:manage-attendance');
    Route::middleware('permission:manage-attendance')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::post('/attendance/record', [AttendanceController::class, 'record'])->name('attendance.record');
        Route::get('/attendance/reports/{type}', [AttendanceController::class, 'reports'])->name('attendance.reports');
    });
    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])
        ->middleware('permission:view-own-attendance')
        ->name('my-attendance');

    // Assessment
    // Word import is registered before the resource so /assessments/import is
    // not swallowed by /assessments/{assessment}.
    Route::middleware('permission:manage-exams|manage-quizzes')->group(function () {
        Route::get('/assessments/import', [AssessmentImportController::class, 'create'])->name('assessments.import');
        Route::post('/assessments/import/parse', [AssessmentImportController::class, 'parse'])->name('assessments.import.parse');
        Route::post('/assessments/import', [AssessmentImportController::class, 'store'])->name('assessments.import.store');
        Route::get('/assessments/import/template', [AssessmentImportController::class, 'template'])->name('assessments.import.template');
    });

    Route::resource('assessments', AssessmentController::class)->middleware('permission:manage-assessments');
    Route::get('/assessments/{assessment}/scores', [AssessmentController::class, 'scores'])
        ->middleware('permission:manage-assessments')
        ->name('assessments.scores');
    Route::resource('exams', ExamController::class)->middleware('permission:manage-exams');
    Route::get('/exams/{exam}/results', [ExamController::class, 'results'])
        ->middleware('permission:manage-exams')
        ->name('exams.results');
    // An exam result is a grade: whoever may publish report cards may correct
    // the marks that feed them, and either may reach this list.
    Route::resource('exam-results', ExamResultController::class)
        ->parameters(['exam-results' => 'result'])
        ->middleware('permission:manage-exams|manage-report-cards');
    Route::resource('report-cards', ReportCardController::class)->middleware('permission:manage-report-cards');
    // A pupil's own grades live in the student portal (`student.grades`) and a
    // guardian's in `guardian.children.grades`; this duplicate named a method
    // nobody wrote and no screen linked to.

    // Learning
    Route::resource('materials', MaterialController::class)->middleware('permission:manage-materials');

    // See the note on `documents.download`. The permission is repeated rather than
    // inherited: `->middleware()` on the resource call above applies to the
    // resource's own routes, not to a separately registered one beside it.
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])
        ->middleware('permission:manage-materials')
        ->name('materials.download');

    // Lesson video playback. Deliberately *not* behind `permission:manage-materials`:
    // students with `view-own-lessons` watch published lesson videos of their own
    // sections, and `MaterialPolicy::stream` is the gate that decides who reads
    // which row. Every staff route above stays as staff-only as it was.
    Route::get('/materials/{material}/stream', [MaterialController::class, 'stream'])
        ->name('materials.stream');

    // Live classroom: the teacher's studio and the oversight list. The policy
    // decides who may start, watch and end a session; this middleware decides
    // who may reach the screens at all.
    Route::middleware('permission:manage-live-sessions')->group(function () {
        Route::get('/live', [LiveSessionController::class, 'index'])->name('live.index');
        Route::get('/live/create', [LiveSessionController::class, 'create'])->name('live.create');
        Route::post('/live', [LiveSessionController::class, 'store'])->name('live.store');
        Route::get('/live/{liveSession}', [LiveSessionController::class, 'show'])->name('live.show');
        Route::post('/live/{liveSession}/start', [LiveSessionController::class, 'start'])->name('live.start');
        Route::post('/live/{liveSession}/end', [LiveSessionController::class, 'end'])->name('live.end');
        Route::delete('/live/{liveSession}', [LiveSessionController::class, 'destroy'])->name('live.destroy');
    });
    Route::resource('assignments', AssignmentController::class)->middleware('permission:manage-assignments');
    Route::get('/assignments/{assignment}/submissions', [SubmissionController::class, 'index'])
        ->middleware('permission:manage-assignments')
        ->name('assignments.submissions');
    Route::get('/my-assignments', [AssignmentController::class, 'myAssignments'])
        ->middleware('permission:submit-assignments')
        ->name('my-assignments');
    Route::resource('submissions', SubmissionController::class)->middleware('permission:manage-submissions');

    // See the note on `documents.download` — and `materials.download` for why the
    // permission is repeated here rather than inherited from the resource.
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])
        ->middleware('permission:manage-submissions')
        ->name('submissions.download');
    Route::resource('quizzes', QuizController::class)->middleware('permission:manage-quizzes');
    Route::get('/my-quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])
        ->middleware('permission:take-quizzes')
        ->name('my-quizzes.attempt');

    // Finance
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::middleware('permission:manage-fee-types|manage-fee-structures')->group(function () {
            Route::get('/fee-types', [FeeStructureController::class, 'index'])->name('fee-types.index');
            Route::get('/fee-structures', [FeeStructureController::class, 'index'])->name('fee-structures.index');
            Route::get('/fee-structures/create', [FeeStructureController::class, 'create'])->name('fee-structures.create');
            Route::post('/fee-structures', [FeeStructureController::class, 'store'])->name('fee-structures.store');
            Route::get('/fee-structures/{feeStructure}', [FeeStructureController::class, 'show'])->name('fee-structures.show');
            Route::get('/fee-structures/{feeStructure}/edit', [FeeStructureController::class, 'edit'])->name('fee-structures.edit');
            Route::put('/fee-structures/{feeStructure}', [FeeStructureController::class, 'update'])->name('fee-structures.update');
        });
        // No fee-assignment screen: the route named a method nobody wrote and
        // no page links to it. Fees are assigned per invoice today.
        Route::resource('discounts', FinanceDiscountController::class)->middleware('permission:manage-discounts');
        // Invoice actions must be declared before the resource so the extra
        // segments are not swallowed by the {invoice} wildcard.
        Route::middleware('permission:manage-invoices')->group(function () {
            Route::post('/invoices/{invoice}/issue', [FinanceInvoiceController::class, 'issue'])->name('invoices.issue');
            Route::post('/invoices/{invoice}/send', [FinanceInvoiceController::class, 'send'])->name('invoices.send');
            Route::get('/invoices/{invoice}/pdf', [FinanceInvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::resource('invoices', FinanceInvoiceController::class);
        });
        Route::middleware('permission:manage-payments')->group(function () {
            Route::get('/payments/offline', [FinancePaymentController::class, 'offline'])->name('payments.offline');
            Route::get('/payments/{payment}/review', [FinancePaymentController::class, 'review'])->name('payments.review');
            Route::post('/payments/{payment}/confirm', [FinancePaymentController::class, 'confirm'])->name('payments.confirm');
            Route::resource('payments', FinancePaymentController::class);
        });
        Route::resource('refunds', FinanceRefundController::class)->middleware('permission:manage-refunds');
        // A pupil's own fees live in the student portal (`student.fees`) and a
        // guardian's in `guardian.children.fees`; this duplicate named a method
        // nobody wrote and no screen linked to.
    });

    // Admissions
    Route::prefix('admissions')->name('admissions.')->middleware('permission:manage-admissions')->group(function () {
        Route::get('/periods', [AdmissionsController::class, 'periods'])->name('periods');
        Route::get('/applications', [AdmissionsController::class, 'applications'])->name('applications');
        Route::get('/applications/{id}', [AdmissionsController::class, 'applicationShow'])->name('applications.show');
        Route::post('/applications/{id}/decide', [AdmissionsController::class, 'applicationDecide'])->name('applications.decide');
        Route::post('/applications/{id}/convert', [AdmissionsController::class, 'applicationConvert'])->name('applications.convert');
        Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
        Route::post('/review/{application}/assign', [ReviewController::class, 'assign'])->name('review.assign');
        Route::post('/review/{application}/priority', [ReviewController::class, 'updatePriority'])->name('review.priority');
        Route::post('/review/{application}/notes', [ReviewController::class, 'addInternalNotes'])->name('review.notes');
        Route::post('/review/bulk-update', [ReviewController::class, 'bulkUpdate'])->name('review.bulk.update');
        Route::get('/review/{application}', [ReviewController::class, 'showForReview'])->name('review.show');
    });

    // Enrollment
    // The list has no waitlist screen: `/enrollments/waitlist` was registered
    // after the resource and was answered by `enrollments.show` with
    // `enrollment = "waitlist"`, behind a method that did not exist as well.
    Route::resource('enrollments', EnrollmentController::class)->middleware('permission:manage-enrollments');

    // Communication
    Route::middleware('permission:manage-messages')->group(function () {
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    });
    Route::resource('announcements', AnnouncementController::class)->middleware('permission:manage-announcements');
    Route::get('/notifications', [MessageController::class, 'notifications'])->name('notifications.index');

    // Documents
    Route::middleware('permission:manage-documents')->group(function () {
        // The resource's own create/store are the upload screen: the "Upload
        // Document" button links to /documents/create and the form posts to
        // /documents. A second /documents/upload pair was registered after the
        // resource, so `documents/{document}` with `document = "upload"`
        // answered it first and the pair was unreachable — as was
        // /documents/categories, which had no method and no screen either.
        Route::resource('documents', DocumentController::class);

        // Uploads live on the private disk with nothing in the web root linking to
        // them, so this is the only way to get one back. It carries the group's
        // `manage-documents` gate and authorizes the record's own `view` policy, so
        // the audience is exactly the people who can already open the document page.
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->name('documents.download');
    });

    // Content management (admin)
    //
    // Named `content.*` on purpose. A resource takes its name from the last URI
    // segment, so these three were registered as `pages.*`, `news.*` and
    // `events.*` — which collided with the public site's `news.*` and
    // `events.*` routes and left the controllers asking for `content.news.show`
    // and `content.events.show`, routes that did not exist: creating, updating
    // or deleting an article or an event saved the row and then answered 500.
    Route::middleware('permission:manage-content')->group(function () {
        Route::get('content/pages/{page}/preview', [ContentPageController::class, 'preview'])->name('content.pages.preview');
        Route::resource('content/pages', ContentPageController::class)->except(['show'])->names('content.pages');
        Route::resource('content/news', NewsController::class)->names('content.news');
        Route::resource('content/events', EventController::class)->names('content.events');
    });

    // Reports
    Route::middleware('permission:manage-reports')->group(function () {
        // The index lists three report names; there is no export method and no
        // screen button that asks for one.
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:manage-settings|manage-schools')->group(function () {
        // One school editor, one URL. /settings/general stays as the historic
        // Settings landing that the sidebar and every breadcrumb pointed at, but
        // now forwards to the school form so the two can never drift apart. The
        // method is on the controller, not a closure here: `php artisan optimize`
        // — which a Laravel Cloud build runs — caches the route table and cannot
        // serialise a closure.
        Route::get('/general', [SchoolSettingsController::class, 'general'])->name('general');
        Route::get('/school', [SchoolSettingsController::class, 'index'])->name('school');
        Route::post('/school', [SchoolSettingsController::class, 'store']);
        Route::get('/academic', [AcademicSettingsController::class, 'index'])->name('academic');
        Route::post('/academic', [AcademicSettingsController::class, 'store']);
        Route::get('/attendance', [AttendanceSettingsController::class, 'index'])->name('attendance');
        Route::post('/attendance', [AttendanceSettingsController::class, 'store']);
        Route::get('/grading', [GradingSettingsController::class, 'index'])->name('grading');
        Route::post('/grading', [GradingSettingsController::class, 'store']);

        // Grading scales and categories are edited one row at a time from the
        // grading screen; these routes back the buttons that used to be dead.
        Route::post('/grading/scales', [GradingSettingsController::class, 'storeScale'])->name('grading.scales.store');
        Route::put('/grading/scales/{scale}', [GradingSettingsController::class, 'updateScale'])->name('grading.scales.update');
        Route::delete('/grading/scales/{scale}', [GradingSettingsController::class, 'destroyScale'])->name('grading.scales.destroy');
        Route::post('/grading/categories', [GradingSettingsController::class, 'storeCategory'])->name('grading.categories.store');
        Route::put('/grading/categories/{category}', [GradingSettingsController::class, 'updateCategory'])->name('grading.categories.update');
        Route::delete('/grading/categories/{category}', [GradingSettingsController::class, 'destroyCategory'])->name('grading.categories.destroy');
        Route::get('/notifications-config', [NotificationSettingsController::class, 'index'])->name('notifications-config');
        Route::post('/notifications-config', [NotificationSettingsController::class, 'store']);
        Route::get('/email', [EmailSettingsController::class, 'index'])->name('email');
        Route::post('/email', [EmailSettingsController::class, 'store']);
        Route::get('/sms', [SmsSettingsController::class, 'index'])->name('sms');
        Route::post('/sms', [SmsSettingsController::class, 'store']);
        Route::get('/security', [SecuritySettingsController::class, 'index'])->name('security');
        Route::post('/security', [SecuritySettingsController::class, 'store']);
        Route::get('/localization', [LocalizationSettingsController::class, 'index'])->name('localization');
        Route::post('/localization', [LocalizationSettingsController::class, 'store']);
        Route::get('/appearance', [AppearanceSettingsController::class, 'index'])->name('appearance');
        Route::post('/appearance', [AppearanceSettingsController::class, 'store'])->name('appearance.store');
        Route::get('/theme', [ThemeSettingsController::class, 'index'])->name('theme');
        Route::post('/theme', [ThemeSettingsController::class, 'store'])->name('theme.store');
        Route::get('/navigation', [NavigationSettingsController::class, 'index'])->name('navigation');
        Route::post('/navigation', [NavigationSettingsController::class, 'store']);
        Route::get('/translations', [TranslationSettingsController::class, 'index'])->name('translations');
        Route::post('/translations', [TranslationSettingsController::class, 'store']);
        Route::post('/translations/interface-copy', [InterfaceTranslationController::class, 'store'])
            ->middleware('role:super_admin')
            ->name('translations.interface-copy.store');
        Route::delete('/translations/interface-copy/{translation}', [InterfaceTranslationController::class, 'destroy'])
            ->middleware('role:super_admin')
            ->name('translations.interface-copy.destroy');
        Route::post('/translations/test', [TranslationSettingsController::class, 'test'])->name('translations.test');
        Route::post('/translations/backfill', [TranslationSettingsController::class, 'backfill'])->name('translations.backfill');
        Route::post('/translations/models', [TranslationSettingsController::class, 'models'])->name('translations.models');
        Route::get('/payments', [PaymentSettingsController::class, 'index'])->name('payments');
        Route::post('/payments', [PaymentSettingsController::class, 'store']);
        Route::get('/payments/logs', [PaymentSettingsController::class, 'logs'])->name('payments.logs');

        Route::resource('users', UserController::class)->middleware('permission:manage-users');
    });

    // Personal settings are available to every authenticated user.
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::post('/profile', [ProfileController::class, 'store']);
        Route::get('/password', [PasswordController::class, 'index'])->name('password');
        Route::post('/password', [PasswordController::class, 'store']);
        Route::get('/preferences', [PreferenceController::class, 'index'])->name('preferences');
        Route::post('/preferences', [PreferenceController::class, 'store']);
        // Light/dark toggle is a personal preference, not an admin setting.
        Route::post('/theme/mode', [ThemeSettingsController::class, 'storeMode'])->name('theme.mode');
        Route::get('/security/two-factor', [SecurityController::class, 'index'])->name('security.two-factor');
        Route::post('/security/two-factor/enable', [SecurityController::class, 'enable'])->name('security.two-factor.enable');
        Route::post('/security/two-factor/disable', [SecurityController::class, 'disable'])->name('security.two-factor.disable');
    });

    // Administration
    // Roles are read-only here: the index screen itself says assignments happen
    // in Settings -> Users, and the resource's other six actions named methods
    // nobody wrote — as did /permissions.
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:manage-roles')
        ->name('roles.index');
    Route::resource('schools', SchoolController::class)->middleware('permission:manage-schools');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:view-audit-logs')
        ->name('audit-logs.index');

    // Student portal
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'index'])->name('dashboard');
        Route::get('/schedule', [StudentPortalController::class, 'schedule'])->name('schedule');
        Route::get('/attendance', [StudentPortalController::class, 'attendance'])->name('attendance');
        Route::get('/grades', [StudentPortalController::class, 'grades'])->name('grades');
        Route::get('/assignments', [StudentPortalController::class, 'assignments'])->name('assignments');
        Route::get('/lessons', [StudentPortalController::class, 'lessons'])->name('lessons');
        Route::get('/fees', [StudentPortalController::class, 'fees'])->name('fees');
    });

    // Guardian portal
    Route::prefix('guardian')->name('guardian.')->middleware('role:guardian')->group(function () {
        Route::get('/dashboard', [GuardianPortalController::class, 'index'])->name('dashboard');
        Route::get('/children', [GuardianPortalController::class, 'children'])->name('children');
        Route::get('/children/{child}/schedule', [GuardianPortalController::class, 'childSchedule'])->name('children.schedule');
        Route::get('/children/{child}/attendance', [GuardianPortalController::class, 'childAttendance'])->name('children.attendance');
        Route::get('/children/{child}/grades', [GuardianPortalController::class, 'childGrades'])->name('children.grades');
        Route::get('/children/{child}/assignments', [GuardianPortalController::class, 'childAssignments'])->name('children.assignments');
        Route::get('/children/{child}/fees', [GuardianPortalController::class, 'childFees'])->name('children.fees');
    });
});

// Public payment links handed to guardians in invoice emails. Access is proven
// by the signature rather than a session, so these sit outside the auth group.
// The signature proves access; the invoice owns the tenant, so the group pins
// it before binding resolves (see PinPublicInvoiceTenant).
Route::prefix('pay')->name('public.invoices.')->middleware(['signed', 'public.invoice.tenant'])->group(function () {
    Route::get('/{invoice}', [PublicInvoicePaymentController::class, 'show'])->name('pay');
    Route::get('/{invoice}/pdf', [PublicInvoicePaymentController::class, 'pdf'])->name('pdf');
    Route::post('/{invoice}/checkout', [PublicInvoicePaymentController::class, 'checkout'])->name('checkout');
    Route::post('/{invoice}/offline', [PublicInvoicePaymentController::class, 'offline'])->name('offline');
});

// Webhook routes (no auth middleware). CSRF-exempt in bootstrap/app.php — a
// gateway cannot hold a token — and rate limited, because the endpoint is open
// to the internet and its only authentication is the per-school secret the
// controller verifies before anything settles.
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/payments/{gateway}', [WebhookController::class, 'handle'])
        ->middleware('throttle:60,1')
        ->name('payments.handle');
});

// MediaMTX hooks (no auth middleware). CSRF-exempt in bootstrap/app.php — the
// live server cannot hold a token — and authenticated by the shared secret in
// `config/media.php`: an unset secret refuses every call.
Route::post('/media/hooks/not-ready', [MediaHookController::class, 'notReady'])->name('media.hooks.not-ready');
Route::post('/media/authorize', MediaAuthorizationController::class)->name('media.authorize');

// Platform/Super Admin routes
Route::middleware(['auth', 'can:access-platform'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/schools', [App\Http\Controllers\Platform\SchoolController::class, 'index'])->name('schools.index');
    Route::get('/schools/{school}', [App\Http\Controllers\Platform\SchoolController::class, 'show'])->name('schools.show');
    Route::get('/health', [HealthController::class, 'index'])->name('health');
    Route::get('/support-access', [SupportAccessController::class, 'index'])->name('support-access');
});
