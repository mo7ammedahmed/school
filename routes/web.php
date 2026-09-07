<?php

declare(strict_types=1);

use App\Http\Controllers\SchoolController;
use App\Http\Controllers\Platform\OrganizationController;
use App\Http\Controllers\Platform\HealthController;
use App\Http\Controllers\Platform\SupportAccessController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdmissionsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admissions\ReviewController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SchoolSelectionController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Finance\DiscountController as FinanceDiscountController;
use App\Http\Controllers\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\Finance\RefundController as FinanceRefundController;
use App\Http\Controllers\Guardian\PortalController as GuardianPortalController;
use App\Http\Controllers\Settings\AcademicSettingsController;
use App\Http\Controllers\Settings\AppearanceSettingsController;
use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\EmailSettingsController;
use App\Http\Controllers\Settings\GradingSettingsController;
use App\Http\Controllers\Settings\LocalizationSettingsController;
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
use App\Http\Controllers\Student\PortalController as StudentPortalController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ProgramsController;
use App\Http\Controllers\Public\AdmissionsController as PublicAdmissionsController;
use App\Http\Controllers\Public\FacilitiesController;
use App\Http\Controllers\Public\TeachersController;
use App\Http\Controllers\Public\NewsController as PublicNewsController;
use App\Http\Controllers\Public\EventsController as PublicEventsController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\FaqController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('public.about');
Route::get('/programs', [ProgramsController::class, 'index'])->name('public.programs');
Route::get('/programs/{program}', [ProgramsController::class, 'show'])->name('public.programs.show');
Route::get('/admissions', [PublicAdmissionsController::class, 'index'])->name('public.admissions');
Route::get('/facilities', [FacilitiesController::class, 'index'])->name('public.facilities');
Route::get('/teachers', [TeachersController::class, 'index'])->name('public.teachers');
Route::get('/teachers/{teacher}', [TeachersController::class, 'show'])->name('public.teachers.show');
Route::get('/news', [PublicNewsController::class, 'index'])->name('news.index');
Route::get('/news/{post}', [PublicNewsController::class, 'show'])->name('news.show');
Route::get('/events', [PublicEventsController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [PublicEventsController::class, 'show'])->name('events.show');
Route::get('/contact', [ContactController::class, 'index'])->name('public.contact');
Route::post('/contact', [ContactController::class, 'store'])->name('public.contact.submit');
Route::get('/faq', [FaqController::class, 'index'])->name('public.faq');

// Apply / Admissions
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
Route::get('/apply/submitted', [PublicAdmissionsController::class, 'submitted'])->name('apply.submitted');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password/{token}', [ResetPasswordController::class, 'store']);
    Route::get('/verify-email', [VerifyEmailController::class, 'create'])->name('verification.notice');
    Route::get('/two-factor-challenge', [TwoFactorAuthenticationController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorAuthenticationController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/select-school', [SchoolSelectionController::class, 'index'])->name('school.select');
    Route::post('/select-school', [SchoolSelectionController::class, 'select']);
});

// Onboarding
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    Route::get('/school-information', [OnboardingController::class, 'schoolInformation'])->name('school-information');
    Route::post('/school-information', [OnboardingController::class, 'storeSchoolInformation']);
    Route::get('/create-school', [OnboardingController::class, 'createSchool'])->name('create-school');
    Route::post('/create-school', [OnboardingController::class, 'storeCreateSchool']);
    Route::get('/academic-year', [OnboardingController::class, 'academicYear'])->name('academic-year');
    Route::post('/academic-year', [OnboardingController::class, 'storeAcademicYear']);
    Route::get('/grades', [OnboardingController::class, 'grades'])->name('grades');
    Route::post('/grades', [OnboardingController::class, 'storeGrades']);
    Route::get('/subjects', [OnboardingController::class, 'subjects'])->name('subjects');
    Route::post('/subjects', [OnboardingController::class, 'storeSubjects']);
    Route::get('/teachers', [OnboardingController::class, 'teachers'])->name('teachers');
    Route::post('/teachers', [OnboardingController::class, 'storeTeachers']);
    Route::get('/students', [OnboardingController::class, 'students'])->name('students');
    Route::post('/students', [OnboardingController::class, 'storeStudents']);
    Route::get('/fee-structure', [OnboardingController::class, 'feeStructure'])->name('fee-structure');
    Route::post('/fee-structure', [OnboardingController::class, 'storeFeeStructure']);
    Route::get('/payment-gateway', [OnboardingController::class, 'paymentGateway'])->name('payment-gateway');
    Route::post('/payment-gateway', [OnboardingController::class, 'storePaymentGateway']);
    Route::get('/finish', [OnboardingController::class, 'finish'])->name('finish');
});

// Authenticated application routes
Route::middleware(['auth', 'school.context'])->prefix('')->name('')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view-dashboard')
        ->name('dashboard');

    // Academics
    Route::prefix('academic-years')->name('academic-years.')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::get('/create', [AcademicYearController::class, 'create'])->name('create');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::get('/{year}', [AcademicYearController::class, 'show'])->name('show');
        Route::get('/{year}/edit', [AcademicYearController::class, 'edit'])->name('edit');
        Route::put('/{year}', [AcademicYearController::class, 'update'])->name('update');
        Route::delete('/{year}', [AcademicYearController::class, 'destroy'])->name('destroy');
        Route::get('/{year}/semesters', [SemesterController::class, 'index'])->name('semesters.index');
    });

    Route::resource('semesters', SemesterController::class)->except(['index']);
    Route::resource('grade-levels', GradeLevelController::class);
    Route::resource('sections', SectionController::class);
    Route::resource('subjects', SubjectController::class);
    Route::get('/subjects/{subject}/offerings', [SubjectController::class, 'offerings'])->name('subjects.offerings');

    // People
    Route::resource('students', StudentController::class);
    Route::resource('guardians', GuardianController::class);
    Route::resource('teachers', TeacherController::class);
    Route::get('/teachers/{teacher}/schedule', [TeacherController::class, 'schedule'])->name('teachers.schedule');

    // Scheduling
    Route::resource('rooms', RoomController::class);
    Route::resource('timetable', TimetableController::class);
    Route::get('/timetable/teacher/{teacher}', [TimetableController::class, 'teacher'])->name('timetable.teacher');
    Route::get('/timetable/class/{section}', [TimetableController::class, 'section'])->name('timetable.section');
    Route::get('/my-schedule', [TimetableController::class, 'mySchedule'])->name('my-schedule');

    // Attendance
    Route::resource('attendance-sessions', AttendanceSessionController::class)->parameters(['attendance-sessions' => 'session']);
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    Route::post('/attendance/record', [AttendanceController::class, 'record'])->name('attendance.record');
    Route::get('/attendance/reports/{type}', [AttendanceController::class, 'reports'])->name('attendance.reports');
    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])->name('my-attendance');

    // Assessment
    Route::resource('assessments', AssessmentController::class);
    Route::get('/assessments/{assessment}/scores', [AssessmentController::class, 'scores'])->name('assessments.scores');
    Route::resource('exams', ExamController::class);
    Route::get('/exams/{exam}/results', [ExamController::class, 'results'])->name('exams.results');
    Route::resource('exam-results', ExamResultController::class)->parameters(['exam-results' => 'result']);
    Route::resource('report-cards', ReportCardController::class);
    Route::get('/my-grades', [ReportCardController::class, 'myGrades'])->name('my-grades');

    // Learning
    Route::resource('materials', MaterialController::class);
    Route::resource('assignments', AssignmentController::class);
    Route::get('/assignments/{assignment}/submissions', [SubmissionController::class, 'index'])->name('assignments.submissions');
    Route::get('/my-assignments', [AssignmentController::class, 'myAssignments'])->name('my-assignments');
    Route::resource('submissions', SubmissionController::class);
    Route::resource('quizzes', QuizController::class);
    Route::get('/my-quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('my-quizzes.attempt');

    // Finance
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/fee-types', [FeeStructureController::class, 'index'])->name('fee-types.index');
        Route::get('/fee-structures', [FeeStructureController::class, 'index'])->name('fee-structures.index');
        Route::get('/fee-structures/create', [FeeStructureController::class, 'create'])->name('fee-structures.create');
        Route::post('/fee-structures', [FeeStructureController::class, 'store'])->name('fee-structures.store');
        Route::get('/fee-structures/{feeStructure}', [FeeStructureController::class, 'show'])->name('fee-structures.show');
        Route::get('/fee-structures/{feeStructure}/edit', [FeeStructureController::class, 'edit'])->name('fee-structures.edit');
        Route::put('/fee-structures/{feeStructure}', [FeeStructureController::class, 'update'])->name('fee-structures.update');
        Route::get('/fee-assignments', [FeeStructureController::class, 'assignments'])->name('fee-assignments.index');
        Route::resource('discounts', FinanceDiscountController::class);
        Route::resource('invoices', FinanceInvoiceController::class);
        Route::resource('payments', FinancePaymentController::class);
        Route::get('/payments/offline', [FinancePaymentController::class, 'offline'])->name('payments.offline');
        Route::get('/payments/{payment}/return', [FinancePaymentController::class, 'return'])->name('payments.return');
        Route::resource('refunds', FinanceRefundController::class);
        Route::get('/my-fees', [FinanceController::class, 'myFees'])->name('my-fees');
    });

    // Admissions
    Route::prefix('admissions')->name('admissions.')->group(function () {
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
    Route::resource('enrollments', EnrollmentController::class);
    Route::get('/enrollments/waitlist', [EnrollmentController::class, 'waitlist'])->name('enrollments.waitlist');

    // Communication
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::resource('announcements', AnnouncementController::class);
    Route::get('/notifications', [MessageController::class, 'notifications'])->name('notifications.index');

    // Documents
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/upload', [DocumentController::class, 'create'])->name('documents.upload');
    Route::post('/documents/upload', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/categories', [DocumentController::class, 'categories'])->name('documents.categories');

    // Content management (admin)
    Route::middleware('permission:manage-content')->group(function () {
        Route::resource('content/news', NewsController::class);
        Route::resource('content/events', EventController::class);
    });

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');

    // Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:manage-settings')->group(function () {
        Route::get('/general', [SchoolSettingsController::class, 'index'])->name('general');
        Route::post('/general', [SchoolSettingsController::class, 'store']);
        Route::get('/school', [SchoolSettingsController::class, 'index'])->name('school');
        Route::post('/school', [SchoolSettingsController::class, 'store']);
        Route::get('/academic', [AcademicSettingsController::class, 'index'])->name('academic');
        Route::post('/academic', [AcademicSettingsController::class, 'store']);
        Route::get('/attendance', [AttendanceSettingsController::class, 'index'])->name('attendance');
        Route::post('/attendance', [AttendanceSettingsController::class, 'store']);
        Route::get('/grading', [GradingSettingsController::class, 'index'])->name('grading');
        Route::post('/grading', [GradingSettingsController::class, 'store']);
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
        Route::post('/appearance', [AppearanceSettingsController::class, 'store']);
        Route::get('/payments', [PaymentSettingsController::class, 'index'])->name('payments');
        Route::post('/payments', [PaymentSettingsController::class, 'store']);
        Route::get('/payments/logs', [PaymentSettingsController::class, 'logs'])->name('payments.logs');

        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::post('/profile', [ProfileController::class, 'store']);
        Route::get('/password', [PasswordController::class, 'index'])->name('password');
        Route::post('/password', [PasswordController::class, 'store']);
        Route::get('/preferences', [PreferenceController::class, 'index'])->name('preferences');
        Route::post('/preferences', [PreferenceController::class, 'store']);
        Route::get('/security/two-factor', [SecurityController::class, 'index'])->name('security.two-factor');
        Route::post('/security/two-factor/enable', [SecurityController::class, 'enable'])->name('security.two-factor.enable');
        Route::post('/security/two-factor/disable', [SecurityController::class, 'disable'])->name('security.two-factor.disable');

        Route::resource('users', UserController::class)->middleware('permission:manage-users');
    });

    // Administration
    Route::resource('roles', RoleController::class)->middleware('permission:manage-roles');
    Route::get('/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:manage-roles')
        ->name('permissions.index');
    Route::resource('schools', SchoolController::class)->middleware('permission:manage-schools');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Student portal
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'index'])->name('dashboard');
        Route::get('/schedule', [StudentPortalController::class, 'schedule'])->name('schedule');
        Route::get('/attendance', [StudentPortalController::class, 'attendance'])->name('attendance');
        Route::get('/grades', [StudentPortalController::class, 'grades'])->name('grades');
        Route::get('/assignments', [StudentPortalController::class, 'assignments'])->name('assignments');
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

// Webhook routes (no auth middleware)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/payments/{gateway}', [WebhookController::class, 'handle'])->name('payments.handle');
});

// Platform/Super Admin routes
Route::middleware(['auth', 'can:access-platform'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/schools', [\App\Http\Controllers\Platform\SchoolController::class, 'index'])->name('schools.index');
    Route::get('/schools/{school}', [\App\Http\Controllers\Platform\SchoolController::class, 'show'])->name('schools.show');
    Route::get('/health', [HealthController::class, 'index'])->name('health');
    Route::get('/support-access', [SupportAccessController::class, 'index'])->name('support-access');
});

Route::get('/debug-db', function () {
    $connection = DB::connection()->getDatabaseName();
    return response()->json(['database' => $connection]);
});


Route::get('/debug-env', function () {
    return response()->json([
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'APP_ENV' => env('APP_ENV'),
    ]);
});
