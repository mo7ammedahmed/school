<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Services\LiveMediaAccess;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveMediaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $viewer;

    private Section $section;

    private LiveSession $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $this->school = School::factory()->create();
        app(TenantContext::class)->set($this->school->id);
        $this->teacher = $this->member('teacher');
        $this->viewer = $this->member('student');
        $this->section = Section::factory()->create(['school_id' => $this->school->id]);
        $student = Student::factory()->create(['school_id' => $this->school->id, 'user_id' => $this->viewer->id]);
        $this->section->students()->attach($student->id, [
            'school_id' => $this->school->id, 'academic_year_id' => $this->section->academic_year_id,
            'status' => 'active', 'enrollment_date' => now()->toDateString(),
        ]);
        $offering = Offering::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->section->academic_year_id,
            'section_id' => $this->section->id,
            'subject_id' => Subject::factory()->create(['school_id' => $this->school->id])->id,
            'teacher_id' => TeacherProfile::factory()->create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id])->id,
        ]);
        $this->lesson = LiveSession::create([
            'school_id' => $this->school->id, 'offering_id' => $offering->id,
            'started_by' => $this->teacher->id, 'title' => 'Private lesson',
            'status' => 'live', 'stream_key' => str_repeat('s', 40),
        ]);
    }

    public function test_media_tokens_are_bound_to_action_path_user_and_expiry(): void
    {
        $access = app(LiveMediaAccess::class);
        $token = $access->issue($this->viewer, $this->lesson, 'read');
        $this->authorizeMedia($token, 'read')->assertNoContent();
        $this->authorizeMedia($token, 'publish')->assertForbidden();
        $this->authorizeMedia($token, 'read', 'another-stream')->assertForbidden();
        $this->authorizeMedia('invalid', 'read')->assertForbidden();
        $this->authorizeMedia('', 'read')->assertForbidden();
        $this->authorizeMedia($access->issue($this->teacher, $this->lesson, 'publish'), 'publish')->assertNoContent();
        $other = $this->member('teacher');
        $this->authorizeMedia($access->issue($other, $this->lesson, 'publish'), 'publish')->assertForbidden();
        $this->travel(121)->minutes();
        $this->authorizeMedia($token, 'read')->assertForbidden();
    }

    public function test_membership_enrollment_permissions_and_status_are_rechecked(): void
    {
        $token = app(LiveMediaAccess::class)->issue($this->viewer, $this->lesson, 'read');
        $this->viewer->memberships()->update(['is_active' => false]);
        $this->authorizeMedia($token, 'read')->assertForbidden();
        $this->viewer->memberships()->update(['is_active' => true]);
        $this->section->students()->updateExistingPivot($this->section->students()->firstOrFail()->getKey(), ['status' => 'withdrawn']);
        $this->authorizeMedia($token, 'read')->assertForbidden();
        $publish = app(LiveMediaAccess::class)->issue($this->teacher, $this->lesson, 'publish');
        $this->teacher->syncRoles([]);
        $this->authorizeMedia($publish, 'publish')->assertForbidden();
        $this->teacher->assignRole('teacher');
        $this->lesson->update(['status' => 'ended']);
        $this->authorizeMedia($publish, 'publish')->assertForbidden();
    }

    public function test_control_api_requires_configured_credentials(): void
    {
        config(['media.api_user' => null, 'media.api_password' => null]);
        $this->postJson('/media/authorize', ['action' => 'api', 'user' => '', 'password' => ''])->assertForbidden();
        config(['media.api_user' => 'controller', 'media.api_password' => 'test-only-secret']);
        $this->postJson('/media/authorize', ['action' => 'api', 'user' => 'controller', 'password' => 'wrong'])->assertForbidden();
        $this->postJson('/media/authorize', ['action' => 'api', 'user' => 'controller', 'password' => 'test-only-secret'])->assertNoContent();
        $this->postJson('/media/authorize', ['action' => 'metrics'])->assertForbidden();
    }

    public function test_hls_segments_require_enrollment_and_never_redirect_to_public_media(): void
    {
        config(['media.hls_internal_url' => 'http://media.local:8888']);
        Http::fake(['media.local:8888/*' => Http::sequence()
            ->push("#EXTM3U\nsegment1.mp4\n", 200)
            ->push("#EXTM3U\nhttps://public.example/secret.mp4\n", 200)]);
        $this->actingAs($this->viewer)->withSession(['school_id' => $this->school->id]);
        $url = '/live/'.$this->lesson->id.'/hls/index.m3u8';
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization'));
        $this->get('/live/'.$this->lesson->id.'/hls/secret.env')->assertNotFound();
        $this->get($url)->assertStatus(502);
        $this->viewer->memberships()->update(['is_active' => false]);
        $this->get($url)->assertRedirect('/select-school');
    }

    public function test_only_authorized_studio_pages_allow_camera_and_microphone(): void
    {
        $this->actingAs($this->teacher)->withSession(['school_id' => $this->school->id]);
        $this->get('/live/'.$this->lesson->id)->assertOk()
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(self), camera=(self)');
        $other = $this->member('principal');
        $this->actingAs($other);
        $this->get('/live/'.$this->lesson->id)->assertOk()
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
            ->assertInertia(fn ($page) => $page->where('media.whipUrl', null)->where('media.publishToken', null));
    }

    private function authorizeMedia(string $token, string $action, ?string $path = null)
    {
        return $this->postJson('/media/authorize', ['token' => $token, 'action' => $action, 'path' => $path ?? $this->lesson->stream_key]);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        UserMembership::factory()->create(['school_id' => $this->school->id, 'user_id' => $user->id, 'is_active' => true]);

        return $user;
    }
}
