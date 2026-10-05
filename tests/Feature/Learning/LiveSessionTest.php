<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Models\Material;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Jobs\FinalizeLiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The live classroom, end to end on the server side.
 *
 * The pieces that can silently break are not the media paths — those are the
 * browser's — but the ownership rules and the recording hand-off: a teacher
 * starting a session for somebody else's subject, a student watching another
 * section's lesson, a recording that never becomes a material. Each is asserted
 * here.
 */
class LiveSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_starts_a_session_for_their_own_offering(): void
    {
        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        $this->signIn($school, $teacherUser, ['manage-live-sessions']);

        $this->post('/live', [
            'offering_id' => $offering->id,
            'title' => 'Mathematics revision',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $session = LiveSession::firstOrFail();

        $this->assertSame($offering->id, $session->offering_id);
        $this->assertSame(LiveSession::STATUS_DRAFT, $session->status);
        $this->assertSame(40, strlen($session->stream_key));
    }

    public function test_a_teacher_cannot_start_a_session_for_another_teachers_offering(): void
    {
        $school = School::factory()->create();
        [$teacherUser] = $this->teacher($school);
        [, $otherTeacher] = $this->teacher($school);
        $foreign = $this->offering($school, $otherTeacher);

        $this->signIn($school, $teacherUser, ['manage-live-sessions']);

        $this->post('/live', [
            'offering_id' => $foreign->id,
            'title' => 'Not mine',
        ])->assertSessionHasErrors('offering_id');

        $this->assertDatabaseCount('live_sessions', 0);
    }

    public function test_ending_a_session_dispatches_the_recording_job(): void
    {
        Queue::fake();

        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        $this->signIn($school, $teacherUser, ['manage-live-sessions']);

        $session = LiveSession::create([
            'offering_id' => $offering->id,
            'started_by' => $teacherUser->id,
            'title' => 'Lesson',
            'status' => LiveSession::STATUS_LIVE,
            'stream_key' => 'test-key',
            'started_at' => now()->subMinutes(30),
        ]);

        $this->post("/live/{$session->id}/end")->assertRedirect(route('live.index'));

        $session->refresh();

        $this->assertSame(LiveSession::STATUS_ENDED, $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertGreaterThanOrEqual(1799, $session->duration_seconds);

        Queue::assertPushed(FinalizeLiveSession::class, fn ($job) => $job->liveSessionId === $session->id);
    }

    public function test_another_teachers_session_cannot_be_ended(): void
    {
        $school = School::factory()->create();
        [$teacherUser] = $this->teacher($school);
        [, $otherTeacher] = $this->teacher($school);
        $offering = $this->offering($school, $otherTeacher);

        $this->signIn($school, $teacherUser, ['manage-live-sessions']);

        $session = LiveSession::create([
            'offering_id' => $offering->id,
            'started_by' => $otherTeacher->user_id,
            'title' => 'Theirs',
            'status' => LiveSession::STATUS_LIVE,
            'stream_key' => 'their-key',
            'started_at' => now(),
        ]);

        $this->post("/live/{$session->id}/end")->assertForbidden();
    }

    public function test_the_finalize_job_publishes_the_recording_as_a_material(): void
    {
        Storage::fake('recordings');
        Storage::fake('local');

        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        $session = LiveSession::create([
            'offering_id' => $offering->id,
            'started_by' => $teacherUser->id,
            'title' => 'Recorded lesson',
            'status' => LiveSession::STATUS_ENDED,
            'stream_key' => 'record-key',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'recording_status' => LiveSession::RECORDING_PENDING,
        ]);

        // Two runs: the first records the size, the second sees it unchanged and
        // publishes — the same handshake the real queue performs with releases.
        Storage::disk('recordings')->put('record-key/recording.mp4', 'fake-video-bytes');

        (new FinalizeLiveSession($session->id))->handle();
        (new FinalizeLiveSession($session->id))->handle();

        $session->refresh();

        $this->assertSame(LiveSession::RECORDING_READY, $session->recording_status);
        $this->assertNotNull($session->material_id);

        $material = Material::firstOrFail();

        $this->assertSame('recording', $material->kind);
        $this->assertSame($offering->id, $material->offering_id);
        $this->assertTrue($material->is_published);
        $this->assertSame($session->id, $material->source_live_session_id);
        Storage::disk('local')->assertExists($material->file_path);

        // The scratch disk holds work in progress, not storage: once the lesson
        // is on the private disk nothing may be left behind there — including
        // the remux output, which used to survive every finalized lesson.
        $this->assertSame([], Storage::disk('recordings')->allFiles('record-key'));
    }

    public function test_a_student_watches_only_their_own_sections_lesson(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        [, $teacher] = $this->teacher($school);
        $mine = $this->offering($school, $teacher, 'MATH');
        $theirs = $this->offering($school, $teacher, 'SCI');

        $studentUser = $this->studentIn($school, $mine->section);

        $lesson = $this->videoMaterial($school, $mine);
        $other = $this->videoMaterial($school, $theirs);

        $this->signIn($school, $studentUser, ['view-own-lessons'], ['student']);

        $this->get("/materials/{$lesson->id}/stream")->assertOk();
        $this->get("/materials/{$other->id}/stream")->assertForbidden();
    }

    public function test_a_document_is_not_streamable_even_for_an_enrolled_student(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        [, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);
        $studentUser = $this->studentIn($school, $offering->section);

        Storage::disk('local')->put('materials/syllabus.pdf', 'pdf-bytes');

        $document = Material::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'title' => 'Syllabus',
            'file_path' => 'materials/syllabus.pdf',
            'file_type' => 'pdf',
            'file_size' => 9,
            'kind' => 'file',
            'is_published' => true,
        ]);

        $this->signIn($school, $studentUser, ['view-own-lessons'], ['student']);

        $this->get("/materials/{$document->id}/stream")->assertForbidden();
    }

    public function test_the_stream_endpoint_answers_range_requests(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        Storage::disk('local')->put('materials/lesson.mp4', 'abcdefghij');

        $material = Material::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'title' => 'Lesson',
            'file_path' => 'materials/lesson.mp4',
            'file_type' => 'mp4',
            'file_size' => 10,
            'kind' => 'video',
            'is_published' => true,
        ]);

        $this->signIn($school, $teacherUser, ['manage-materials']);

        $full = $this->get("/materials/{$material->id}/stream");
        $full->assertOk();
        $this->assertSame('bytes', $full->headers->get('accept-ranges'));

        $range = $this->withHeaders(['Range' => 'bytes=2-5'])->get("/materials/{$material->id}/stream");
        $range->assertStatus(206);
        $this->assertSame('bytes 2-5/10', $range->headers->get('content-range'));

        ob_start();
        $range->baseResponse->sendContent();
        $body = (string) ob_get_clean();

        $this->assertSame('cdef', $body);
    }

    public function test_a_student_lessons_page_lists_their_sections_recordings(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        [, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);
        $studentUser = $this->studentIn($school, $offering->section);

        $this->videoMaterial($school, $offering, 'Recorded algebra');

        $this->signIn($school, $studentUser, ['view-own-lessons'], ['student']);

        $response = $this->get('/student/lessons');

        $response->assertOk();

        $recordings = $response->viewData('page')['props']['recordings'];

        $this->assertCount(1, $recordings);
        $this->assertSame('Recorded algebra', $recordings[0]['title']);
    }

    public function test_the_student_player_is_given_both_watch_paths(): void
    {
        config([
            'media.webrtc_url' => 'https://media.example.test/',
            'media.hls_url' => 'https://media.example.test:8443/',
        ]);

        $school = School::factory()->create();
        [, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);
        $studentUser = $this->studentIn($school, $offering->section);

        $this->signIn($school, $studentUser, ['view-own-lessons'], ['student']);

        // Both are named, and the trailing slashes are already gone: the player
        // appends the session's own path to whichever it uses, and a page that
        // carries only the WHEP address cannot show a lesson on a network that
        // blocks the UDP port WebRTC needs.
        $this->assertSame([
            'configured' => true,
            'webrtcUrl' => 'https://media.example.test',
            'hlsUrl' => 'https://media.example.test:8443',
        ], $this->get('/student/lessons')->viewData('page')['props']['media']);
    }

    public function test_hls_alone_still_counts_as_a_configured_live_player(): void
    {
        config(['media.webrtc_url' => null, 'media.hls_url' => 'https://media.example.test']);

        $school = School::factory()->create();
        [, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);
        $studentUser = $this->studentIn($school, $offering->section);

        $this->signIn($school, $studentUser, ['view-own-lessons'], ['student']);

        // A deployment with no WebRTC address at all can still show a lesson,
        // and telling the pupil there is no live viewing would be false.
        $this->assertSame([
            'configured' => true,
            'webrtcUrl' => null,
            'hlsUrl' => 'https://media.example.test',
        ], $this->get('/student/lessons')->viewData('page')['props']['media']);
    }

    public function test_the_media_hook_requires_the_shared_secret(): void
    {
        config(['media.hook_secret' => 'a-secret']);

        $this->post('/media/hooks/not-ready?path=whatever')->assertForbidden();
        $this->post('/media/hooks/not-ready?path=whatever', [], ['X-Media-Secret' => 'wrong'])->assertForbidden();
        $this->post('/media/hooks/not-ready?path=unknown-key', [], ['X-Media-Secret' => 'a-secret'])->assertOk();
    }

    public function test_a_lesson_recorded_in_segments_is_published_whole(): void
    {
        if (! $this->ffmpegIsAvailable()) {
            $this->markTestSkipped('ffmpeg is not installed, so concatenation cannot run here.');
        }

        Storage::fake('recordings');
        Storage::fake('local');

        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        $session = $this->endedSession($school, $offering, $teacherUser, 'segmented-key');

        $this->clip('segmented-key/2026-10-04_01-00-00.mp4');
        $this->clip('segmented-key/2026-10-04_01-00-01.mp4');

        (new FinalizeLiveSession($session->id))->handle();
        (new FinalizeLiveSession($session->id))->handle();

        $material = Material::firstOrFail();

        // One second plus one second: the whole lesson, not its last segment.
        $this->assertSame(2, $material->duration_seconds);

        // The segments, the concat list and the concat output all went.
        $this->assertSame([], Storage::disk('recordings')->allFiles('segmented-key'));
    }

    public function test_finalizing_publishes_one_file_and_clears_every_segment(): void
    {
        Storage::fake('recordings');
        Storage::fake('local');

        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        $session = $this->endedSession($school, $offering, $teacherUser, 'many-segments');

        Storage::disk('recordings')->put('many-segments/2026-10-04_01-00-00.mp4', 'first-segment');
        Storage::disk('recordings')->put('many-segments/2026-10-04_01-00-01.mp4', 'second-segment');

        (new FinalizeLiveSession($session->id))->handle();
        (new FinalizeLiveSession($session->id))->handle();

        $session->refresh();

        $this->assertSame(LiveSession::RECORDING_READY, $session->recording_status);
        Storage::disk('local')->assertExists(Material::firstOrFail()->file_path);

        // Concatenation of unusable bytes cannot succeed; the newest segment is
        // still published, and nothing is left on the scratch disk either way.
        $this->assertSame([], Storage::disk('recordings')->allFiles('many-segments'));
    }

    public function test_pruning_deletes_scratch_files_whose_lesson_is_already_published(): void
    {
        Storage::fake('recordings');

        $school = School::factory()->create();
        [$teacherUser, $teacher] = $this->teacher($school);
        $offering = $this->offering($school, $teacher);

        LiveSession::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'started_by' => $teacherUser->id,
            'title' => 'Published lesson',
            'status' => LiveSession::STATUS_ENDED,
            'stream_key' => 'published-key',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'recording_status' => LiveSession::RECORDING_READY,
        ]);

        LiveSession::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'started_by' => $teacherUser->id,
            'title' => 'Still being processed',
            'status' => LiveSession::STATUS_ENDED,
            'stream_key' => 'pending-key',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'recording_status' => LiveSession::RECORDING_PENDING,
        ]);

        Storage::disk('recordings')->put('published-key/newest.mp4', 'video');
        Storage::disk('recordings')->put('published-key/older-segment.mp4', 'video');
        Storage::disk('recordings')->put('pending-key/in-flight.mp4', 'video');

        $this->artisan('live-sessions:prune')->assertSuccessful();

        Storage::disk('recordings')->assertMissing('published-key/newest.mp4');
        Storage::disk('recordings')->assertMissing('published-key/older-segment.mp4');

        // The lesson still waiting for the finalize job keeps its recording: a
        // young file is never stale, whatever its session says.
        Storage::disk('recordings')->assertExists('pending-key/in-flight.mp4');
        $this->assertSame(['pending-key'], Storage::disk('recordings')->directories());
    }

    public function test_pruning_deletes_orphan_files_past_the_retention_window(): void
    {
        Storage::fake('recordings');

        Storage::disk('recordings')->put('orphan-key/abandoned.mp4', 'video');
        touch(
            Storage::disk('recordings')->path('orphan-key/abandoned.mp4'),
            now()->subDays(3)->getTimestamp(),
        );

        $this->artisan('live-sessions:prune')->assertSuccessful();

        Storage::disk('recordings')->assertMissing('orphan-key/abandoned.mp4');
        $this->assertSame([], Storage::disk('recordings')->directories());
    }

    public function test_the_retention_window_can_be_overridden_for_a_manual_run(): void
    {
        Storage::fake('recordings');

        Storage::disk('recordings')->put('orphan-key/three-days.mp4', 'video');
        touch(
            Storage::disk('recordings')->path('orphan-key/three-days.mp4'),
            now()->subDays(3)->getTimestamp(),
        );

        $this->artisan('live-sessions:prune', ['--hours' => 72])->assertSuccessful();

        Storage::disk('recordings')->assertExists('orphan-key/three-days.mp4');
    }

    /**
     * Sign in as an existing user (a teacher or a student fixture) rather than
     * creating a fresh one — the fixture's id is what the policy checks.
     *
     * The catalogue rows exist without being granted, which is the production
     * shape: `hasPermissionTo()` throws when a permission is missing from the
     * database, and a policy that asks about a staff permission must get `false`
     * for a student, not an exception.
     *
     * @param  list<string>  $permissions
     * @param  list<string>  $roles
     */
    private function signIn(School $school, User $user, array $permissions, array $roles = []): void
    {
        foreach (['manage-materials', 'manage-live-sessions', 'view-own-lessons'] as $catalogue) {
            Permission::firstOrCreate(['name' => $catalogue, 'guard_name' => 'web']);
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo($permissions);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);
        $this->app->make(TenantContext::class)->set($school->id);
    }

    /**
     * An ended session whose recording the finalize job still owes.
     */
    private function endedSession(School $school, Offering $offering, User $teacherUser, string $streamKey): LiveSession
    {
        return LiveSession::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'started_by' => $teacherUser->id,
            'title' => 'Recorded lesson',
            'status' => LiveSession::STATUS_ENDED,
            'stream_key' => $streamKey,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'recording_status' => LiveSession::RECORDING_PENDING,
        ]);
    }

    /**
     * A real one-second video clip on the recordings disk.
     *
     * Two of these side by side are the shape MediaMTX leaves behind when a
     * lesson runs past one record-segment, and the shape the job has to publish
     * as one lesson.
     */
    private function clip(string $path): void
    {
        // ffmpeg writes the file but will not create the directory for it.
        Storage::disk('recordings')->makeDirectory(dirname($path));

        $result = Process::run([
            (string) config('media.ffmpeg'),
            '-v', 'error',
            '-y',
            '-f', 'lavfi',
            '-i', 'testsrc=duration=1:size=160x120:rate=10',
            '-c:v', 'mpeg4',
            Storage::disk('recordings')->path($path),
        ]);

        $this->assertTrue($result->successful(), 'ffmpeg could not write the clip: '.$result->errorOutput());
    }

    private function ffmpegIsAvailable(): bool
    {
        return Process::run([(string) config('media.ffmpeg'), '-version'])->successful();
    }

    /**
     * @return array{0: User, 1: TeacherProfile}
     */
    private function teacher(School $school): array
    {
        $user = User::factory()->create();
        $teacher = TeacherProfile::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        return [$user, $teacher];
    }

    private function offering(School $school, TeacherProfile $teacher, string $code = 'MATH'): Offering
    {
        app(TenantContext::class)->set($school->id);

        $subject = Subject::factory()->create([
            'school_id' => $school->id,
            'code' => $code.'-'.uniqid(),
        ]);

        $section = Section::factory()->create(['school_id' => $school->id]);

        return Offering::create([
            'school_id' => $school->id,
            'academic_year_id' => $section->academic_year_id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'teacher_id' => $teacher->id,
            'name' => $code.' — A',
        ]);
    }

    private function studentIn(School $school, Section $section): User
    {
        app(TenantContext::class)->set($school->id);

        $user = User::factory()->create();

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
        ]);

        $section->students()->attach($student->id, [
            'school_id' => $school->id,
            'academic_year_id' => $section->academic_year_id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return $user;
    }

    private function videoMaterial(School $school, Offering $offering, string $title = 'Lesson video'): Material
    {
        app(TenantContext::class)->set($school->id);

        $path = 'materials/'.uniqid().'.mp4';
        Storage::disk('local')->put($path, 'video-bytes');

        return Material::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'title' => $title,
            'file_path' => $path,
            'file_type' => 'mp4',
            'file_size' => 11,
            'kind' => 'video',
            'is_published' => true,
        ]);
    }
}
