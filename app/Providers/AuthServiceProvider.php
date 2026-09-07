<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Domain\People\Policies\StudentPolicy;
use App\Domain\People\Policies\TeacherPolicy;
use App\Domain\Academics\Policies\AcademicYearPolicy;
use App\Domain\Finance\Policies\InvoicePolicy;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Content\Policies\FaqPolicy;
use App\Domain\Content\Policies\StaffProfilePolicy;
use App\Domain\Content\Policies\ContactLeadPolicy;
use App\Domain\Compliance\Policies\AuditLogPolicy;
use App\Policies\UserPolicy;
use App\Policies\StudentPolicy as AppStudentPolicy;
use App\Policies\TeacherPolicy as AppTeacherPolicy;
use App\Policies\GuardianPolicy;
use App\Policies\SectionPolicy;
use App\Policies\ClassroomPolicy;
use App\Policies\RoomPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\GradeLevelPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\TimetablePolicy;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\StaffProfile;
use App\Domain\Content\Models\ContactLead;
use App\Domain\Compliance\Models\AuditLog;
use App\Models\User;
use App\Models\Student as AppStudent;
use App\Models\Teacher as AppTeacher;
use App\Models\Guardian as AppGuardian;
use App\Models\Section as AppSection;
use App\Models\Classroom as AppClassroom;
use App\Domain\Scheduling\Models\Room as AppRoom;
use App\Models\Subject as AppSubject;
use App\Models\GradeLevel as AppGradeLevel;
use App\Models\Enrollment as AppEnrollment;
use App\Models\Timetable as AppTimetable;

class AuthServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Student::class => StudentPolicy::class,
        TeacherProfile::class => TeacherPolicy::class,
        AcademicYear::class => AcademicYearPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Faq::class => FaqPolicy::class,
        StaffProfile::class => StaffProfilePolicy::class,
        ContactLead::class => ContactLeadPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        User::class => UserPolicy::class,
        AppStudent::class => AppStudentPolicy::class,
        AppTeacher::class => AppTeacherPolicy::class,
        AppGuardian::class => GuardianPolicy::class,
        AppSection::class => SectionPolicy::class,
        AppClassroom::class => ClassroomPolicy::class,
        AppRoom::class => RoomPolicy::class,
        AppSubject::class => SubjectPolicy::class,
        AppGradeLevel::class => GradeLevelPolicy::class,
        AppEnrollment::class => EnrollmentPolicy::class,
        AppTimetable::class => TimetablePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(fn($user, $ability) => $user->hasRole('super_admin') ? true : null);
    }
}
