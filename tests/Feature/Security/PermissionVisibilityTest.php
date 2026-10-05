<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Learning\Models\Submission;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public static function roles(): array
    {
        return array_combine(
            ['super_admin', 'school_admin', 'principal', 'registrar', 'teacher', 'accountant', 'student', 'guardian'],
            array_map(fn ($role) => [$role], ['super_admin', 'school_admin', 'principal', 'registrar', 'teacher', 'accountant', 'student', 'guardian']),
        );
    }

    #[DataProvider('roles')]
    public function test_dashboard_props_contain_only_authorized_statistics(string $role): void
    {
        $school = School::factory()->create();
        $actor = $this->signIn($school, $role);
        $fields = [
            'total_students' => 'manage-students',
            'total_teachers' => 'manage-teachers',
            'total_classes' => 'manage-sections',
            'total_revenue' => 'manage-payments',
            'attendance_rate' => 'manage-attendance',
            'pending_payments' => 'manage-invoices',
        ];
        $expected = array_keys(array_filter($fields, fn ($permission) => $actor->can($permission)));
        $response = $this->get('/dashboard')->assertOk();
        $actual = array_keys($response->viewData('page')['props']['stats']);
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    public function test_school_admin_cannot_offer_or_assign_platform_roles(): void
    {
        $school = School::factory()->create();
        $this->signIn($school, 'school_admin');
        $platformRole = Role::findByName('super_admin');
        $foreignGuardRole = Role::create(['name' => 'external-admin', 'guard_name' => 'api']);
        $this->get('/settings/users/create')->assertOk()->assertInertia(fn ($page) => $page
            ->where('roles', fn ($roles) => ! collect($roles)->contains('id', $platformRole->id)
                && ! collect($roles)->contains('id', $foreignGuardRole->id)));
        $this->post('/settings/users', [
            'name' => 'Escalation', 'email' => 'escalation@example.com',
            'password' => 'safe-test-password', 'password_confirmation' => 'safe-test-password',
            'roles' => [$platformRole->id],
        ])->assertSessionHasErrors('roles.0');
        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.com']);

        $member = $this->member($school);
        $this->put('/settings/users/'.$member->id, [
            'name' => 'Escalated', 'email' => $member->email, 'roles' => [$platformRole->id],
        ])->assertSessionHasErrors('roles.0');
        $this->assertSame($member->name, $member->fresh()->name);
        $this->assertFalse($member->fresh()->hasRole('super_admin'));
    }

    public function test_account_mutations_refuse_platform_and_shared_accounts(): void
    {
        $school = School::factory()->create();
        $this->signIn($school, 'school_admin');
        $platformUser = $this->member($school);
        $platformUser->assignRole('super_admin');
        $shared = $this->member($school);
        UserMembership::factory()->create(['user_id' => $shared->id, 'school_id' => School::factory()->create()->id]);

        foreach ([$platformUser, $shared] as $target) {
            $this->get('/settings/users/'.$target->id)->assertOk()
                ->assertInertia(fn ($page) => $page->where('user.can_update', false));
            $this->get('/settings/users/'.$target->id.'/edit')->assertForbidden();
            $this->put('/settings/users/'.$target->id, ['name' => 'Hijacked'])->assertForbidden();
            $this->delete('/settings/users/'.$target->id)->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => $target->name]);
        }
    }

    public function test_inactive_local_accounts_can_be_managed(): void
    {
        $school = School::factory()->create();
        $this->signIn($school, 'school_admin');
        $member = $this->member($school, false);
        $this->get('/settings/users/'.$member->id.'/edit')->assertOk();
        $this->put('/settings/users/'.$member->id, [
            'name' => $member->name, 'email' => $member->email, 'is_active' => true,
            'roles' => [Role::findByName('teacher')->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->hasRole('teacher'));
    }

    public function test_school_management_cannot_read_reassign_or_delete_foreign_schools(): void
    {
        $school = School::factory()->create();
        $actor = $this->signIn($school, 'school_admin');
        $actor->givePermissionTo('manage-schools');
        $foreign = School::factory()->create();
        $this->get('/schools/'.$school->id)->assertOk();
        $this->get('/schools/'.$foreign->id)->assertForbidden();
        $this->get('/schools/'.$foreign->id.'/edit')->assertForbidden();
        $this->put('/schools/'.$foreign->id, [
            'organization_id' => $school->organization_id, 'name_en' => 'Stolen school',
        ])->assertForbidden();
        $this->delete('/schools/'.$foreign->id)->assertForbidden();
        $this->assertDatabaseHas('schools', ['id' => $foreign->id, 'organization_id' => $foreign->organization_id, 'deleted_at' => null]);
    }

    public function test_student_cannot_read_onboarding_organizations_or_edit_them_through_translation(): void
    {
        $school = School::factory()->create();
        $this->signIn($school, 'student');
        $this->get('/onboarding/create-school')->assertForbidden();
        $this->postJson('/translate/save', [
            'table' => 'organizations', 'id' => $school->organization_id,
            'source_column' => 'name', 'source_value' => 'Hijacked organization',
            'column' => 'name_ar', 'value' => 'اسم',
        ])->assertForbidden();
        $this->assertDatabaseMissing('organizations', ['id' => $school->organization_id, 'name' => 'Hijacked organization']);
    }

    public function test_personal_routes_fail_closed_or_forward_to_the_personal_portal(): void
    {
        $school = School::factory()->create();
        $this->signIn($school, 'student');
        $this->get('/my-attendance')->assertNotFound();
        $this->get('/my-assignments')->assertRedirect('/student/assignments');
        $this->get('/my-schedule')->assertRedirect('/student/schedule');
        $this->signIn($school, 'teacher');
        $this->get('/my-schedule')->assertNotFound();
    }

    public function test_students_receive_only_published_quizzes_for_active_enrollments_without_answer_keys(): void
    {
        $school = School::factory()->create();
        $actor = $this->signIn($school, 'student');
        $this->post('/select-school', ['school_id' => $school->id])->assertRedirect('/student/dashboard');
        $student = Student::factory()->create(['school_id' => $school->id, 'user_id' => $actor->id]);
        $section = Section::factory()->create(['school_id' => $school->id]);
        $teacher = TeacherProfile::factory()->create(['school_id' => $school->id]);
        $offering = Offering::create([
            'school_id' => $school->id, 'academic_year_id' => $section->academic_year_id,
            'section_id' => $section->id, 'teacher_id' => $teacher->id,
            'subject_id' => Subject::factory()->create(['school_id' => $school->id])->id,
        ]);
        $quiz = Quiz::create([
            'school_id' => $school->id, 'offering_id' => $offering->id,
            'title' => 'Private quiz', 'is_published' => true,
            'questions' => [['type' => 'short_answer', 'prompt' => 'Question?', 'answer' => 'SECRET ANSWER', 'points' => 1, 'solution' => 'SECRET SOLUTION']],
        ]);
        $url = '/my-quizzes/'.$quiz->id.'/attempt';
        $this->get($url)->assertForbidden();
        $section->students()->attach($student->id, [
            'school_id' => $school->id, 'academic_year_id' => $section->academic_year_id,
            'enrollment_date' => now()->toDateString(), 'status' => 'active',
        ]);
        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->where('canManage', false)->where('quiz.questions.0.answer', null)
            ->missing('quiz.questions.0.solution')->missing('quiz.offering'));
        $quiz->update(['is_published' => false]);
        $this->get($url)->assertForbidden();
        $quiz->update(['is_published' => true]);
        $student->enrollments()->update(['status' => 'withdrawn']);
        $this->get($url)->assertForbidden();
    }

    public function test_new_permissions_are_not_implicitly_granted_to_staff(): void
    {
        Permission::create(['name' => 'manage-new-sensitive-feature', 'guard_name' => 'web']);
        $this->seed(RoleSeeder::class);
        foreach (['school_admin', 'principal', 'registrar', 'teacher', 'accountant', 'student', 'guardian'] as $role) {
            $this->assertFalse(Role::findByName($role)->hasPermissionTo('manage-new-sensitive-feature'));
        }
        $this->assertTrue(Role::findByName('super_admin')->hasPermissionTo('manage-new-sensitive-feature'));
    }

    public function test_portals_hide_drafts_future_grades_and_private_profile_fields(): void
    {
        $school = School::factory()->create();
        $actor = $this->signIn($school, 'student');
        $student = Student::factory()->create(['school_id' => $school->id, 'user_id' => $actor->id]);
        $section = Section::factory()->create(['school_id' => $school->id]);
        $offering = Offering::create([
            'school_id' => $school->id, 'academic_year_id' => $section->academic_year_id,
            'section_id' => $section->id,
            'subject_id' => Subject::factory()->create(['school_id' => $school->id])->id,
            'teacher_id' => TeacherProfile::factory()->create(['school_id' => $school->id])->id,
        ]);
        $section->students()->attach($student->id, [
            'school_id' => $school->id, 'academic_year_id' => $section->academic_year_id,
            'enrollment_date' => now()->toDateString(), 'status' => 'active',
        ]);
        Assignment::create(['school_id' => $school->id, 'offering_id' => $offering->id,
            'title' => 'Secret draft', 'due_date' => now()->addDay(), 'max_score' => 100, 'is_published' => false]);
        $published = Assignment::create(['school_id' => $school->id, 'offering_id' => $offering->id,
            'title' => 'Released work', 'due_date' => now()->addDay(), 'max_score' => 100, 'is_published' => true]);
        foreach ([null, now()->addDay()] as $publication) {
            ReportCard::create(['school_id' => $school->id, 'student_id' => $student->id,
                'academic_year_id' => $section->academic_year_id, 'gpa' => 4, 'published_at' => $publication]);
        }
        $released = ReportCard::create(['school_id' => $school->id, 'student_id' => $student->id,
            'academic_year_id' => $section->academic_year_id, 'gpa' => 3, 'published_at' => now()->subDay()]);
        Invoice::factory()->create(['school_id' => $school->id, 'student_id' => $student->id, 'status' => 'draft']);
        $issued = Invoice::factory()->create(['school_id' => $school->id, 'student_id' => $student->id,
            'status' => 'partially_paid', 'total_amount' => 100, 'balance_due' => 75, 'amount_paid' => 25]);
        $this->get('/student/dashboard')->assertOk()->assertInertia(fn ($page) => $page
            ->where('stats.average_grade', 3)->where('stats.pending_assignments', 1)->where('stats.outstanding_fees', 75)
            ->missing('student.metadata')->missing('student.national_id_number'));
        $this->get('/student/assignments')->assertOk()->assertInertia(fn ($page) => $page
            ->has('assignments.data', 1)->where('assignments.data.0.id', $published->id));
        $this->get('/student/grades')->assertOk()->assertInertia(fn ($page) => $page
            ->has('reportCards.data', 1)->where('reportCards.data.0.id', $released->id));
        $this->get('/student/fees')->assertOk()->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)->where('invoices.data.0.id', $issued->id));
        Submission::create(['school_id' => $school->id, 'student_id' => $student->id,
            'assignment_id' => $published->id, 'content' => 'Finished', 'submitted_at' => now()]);
        $this->get('/student/dashboard')->assertOk()->assertInertia(fn ($page) => $page->where('stats.pending_assignments', 0));

        $guardianUser = $this->signIn($school, 'guardian');
        $this->post('/select-school', ['school_id' => $school->id])->assertRedirect('/guardian/dashboard');
        $guardian = Guardian::create(['school_id' => $school->id, 'user_id' => $guardianUser->id,
            'first_name' => 'Known', 'last_name' => 'Guardian']);
        $guardian->students()->attach($student->id, ['school_id' => $school->id]);
        $this->get('/guardian/dashboard')->assertOk()->assertInertia(fn ($page) => $page
            ->where('guardian.name', 'Known Guardian')->where('children.0.outstanding_fees', 75)->missing('guardian.national_id_number'));
        foreach (['assignments' => ['assignments', $published->id], 'grades' => ['reportCards', $released->id], 'fees' => ['invoices', $issued->id]] as $path => [$prop, $id]) {
            $this->get('/guardian/children/'.$student->id.'/'.$path)->assertOk()->assertInertia(fn ($page) => $page
                ->has($prop.'.data', 1)->where($prop.'.data.0.id', $id)->missing('child.metadata'));
        }
        $other = Student::factory()->create(['school_id' => $school->id]);
        $this->get('/guardian/children/'.$other->id.'/grades')->assertForbidden();
    }

    private function member(School $school, bool $active = true): User
    {
        $user = User::factory()->create();
        UserMembership::factory()->create(['user_id' => $user->id, 'school_id' => $school->id, 'is_active' => $active]);

        return $user;
    }

    private function signIn(School $school, string $role): User
    {
        $user = $this->member($school);
        $user->assignRole($role);
        $this->actingAs($user)->withSession(['school_id' => $school->id]);
        app(TenantContext::class)->set($school->id);

        return $user;
    }
}
