<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Subject;
use App\Domain\Learning\Models\Material;
use App\Domain\Schools\Models\School;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Which disk holds a school's private files is a deployment decision, not a
 * fact about the code.
 *
 * `storage/app/private` is persistent on a machine you own and *not* on Laravel
 * Cloud, whose filesystem is ephemeral and per-replica: a document written in
 * one request is gone after the next deploy, and a second replica never had it.
 * A Cloud deployment therefore points the app at a private bucket, and every
 * reader has to follow — an upload that lands on the bucket while the download
 * route still looks in `storage/app/private` is a feature that breaks the day it
 * is deployed, silently, on the pages customers use most.
 *
 * The disk a remote file is *served* from is the second half. A bucket has no
 * filesystem path, so the stream route cannot hand `<video>` a `BinaryFileResponse`
 * — it hands it a short-lived signed URL that the bucket answers ranges for.
 * The fallback matters too: a private disk whose driver cannot sign must still
 * play, from a temporary local copy rather than a 500.
 */
class PrivateDiskFollowsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private const CONTENTS = '%PDF-1.4 the real bytes of the document';

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->school = School::factory()->create();
    }

    public function test_an_upload_lands_on_the_configured_private_disk(): void
    {
        $this->privateDiskIs('bucket');

        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->createWithContent('report.pdf', self::CONTENTS),
        ])->assertSessionDoesntHaveErrors();

        $path = (string) DB::table('documents')->value('file_path');

        $this->assertSame(
            self::CONTENTS,
            Storage::disk('bucket')->get($path),
            'The upload did not land on the configured private disk.',
        );
        $this->assertFalse(
            Storage::disk('local')->exists($path),
            'The upload was written to the old disk as well, so the configured one is not the one in use.',
        );
    }

    public function test_the_download_reads_the_configured_disk_back(): void
    {
        $this->privateDiskIs('bucket');

        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->createWithContent('report.pdf', self::CONTENTS),
        ]);

        $id = DB::table('documents')->value('id');

        $response = $this->get("/documents/{$id}/download");

        $response->assertOk();
        $this->assertSame(self::CONTENTS, $response->streamedContent());
    }

    public function test_a_video_on_a_remote_disk_is_handed_a_signed_url(): void
    {
        $this->privateDiskIs('bucket');

        Storage::disk('bucket')->put('materials/lesson.mp4', 'abcdefghij');
        Storage::disk('bucket')->buildTemporaryUrlsUsing(
            fn (string $path, DateTimeInterface $expiration): string => 'https://bucket.example.test/'.$path.'?expires='.$expiration->getTimestamp(),
        );

        $material = $this->videoMaterial('materials/lesson.mp4', 10);

        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $response = $this->get("/materials/{$material->id}/stream");

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'https://bucket.example.test/materials/lesson.mp4?',
            (string) $response->headers->get('Location'),
            'A remote disk has no local path, so playback must be redirected to a signed URL.',
        );
    }

    public function test_a_remote_disk_that_cannot_sign_is_served_from_a_temporary_copy(): void
    {
        $this->privateDiskIs('nosign');

        Storage::disk('nosign')->put('materials/lesson.mp4', 'abcdefghij');

        // `Storage::fake()` signs everything by default, so a disk that cannot
        // sign has to say so. This is the shape of a driver without presigning
        // support (plain FTP, a custom adapter): the route must fall back rather
        // than put a broken player in front of a lesson.
        Storage::disk('nosign')->buildTemporaryUrlsUsing(
            fn (): string => throw new RuntimeException('This disk cannot sign URLs.'),
        );

        $material = $this->videoMaterial('materials/lesson.mp4', 10);

        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $full = $this->get("/materials/{$material->id}/stream");
        $full->assertOk();
        $this->assertSame('bytes', $full->headers->get('accept-ranges'));

        $range = $this->withHeaders(['Range' => 'bytes=2-5'])->get("/materials/{$material->id}/stream");
        $range->assertStatus(206);
        $this->assertSame('bytes 2-5/10', $range->headers->get('content-range'));
    }

    /**
     * Point the app at a faked disk, the way a Cloud deployment points it at a
     * bucket. The fake has no configured driver, which is what makes the stream
     * route treat it as remote.
     */
    private function privateDiskIs(string $name): void
    {
        Storage::fake($name);

        config(['filesystems.private' => $name]);
    }

    private function videoMaterial(string $path, int $size): Material
    {
        $teacherId = DB::table('teacher_profiles')->insertGetId([
            'school_id' => $this->school->id,
            'first_name' => 'Sami',
            'last_name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $offeringId = DB::table('offerings')->insertGetId([
            'school_id' => $this->school->id,
            'academic_year_id' => AcademicYear::factory()
                ->create(['school_id' => $this->school->id])->id,
            'subject_id' => Subject::factory()->create(['school_id' => $this->school->id])->id,
            'teacher_id' => $teacherId,
            'name' => 'Mathematics — Term 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Material::create([
            'school_id' => $this->school->id,
            'offering_id' => $offeringId,
            'title' => 'Lesson',
            'file_path' => $path,
            'file_type' => 'mp4',
            'file_size' => $size,
            'kind' => 'video',
            'is_published' => true,
        ]);
    }
}
