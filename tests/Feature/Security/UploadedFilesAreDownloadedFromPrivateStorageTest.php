<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Moving uploads onto the private disk removed the accidental `/storage/...`
 * back door — which was the point, and which also left the feature with no way to
 * get a file back at all. No route returned the bytes, and the three show pages
 * declared `file_path` in their prop types without ever rendering it.
 *
 * The audience is not a new question. Every one of these models already answers
 * "may this person open this record?" through a policy `view` ability that is a
 * staff permission *and* a same-school check, so a download authorized by `view`
 * grants exactly the people who could already read the record — no guardian, no
 * student and no cross-school user gains anything they did not have.
 *
 * What the route must not become is a file server: the path in the database is
 * used to open a file on disk, so a stored path that points outside the disk
 * would turn "download this document" into "read any file the PHP process can".
 */
class UploadedFilesAreDownloadedFromPrivateStorageTest extends TestCase
{
    use RefreshDatabase;

    private const CONTENTS = '%PDF-1.4 the real bytes of the document';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->school = School::factory()->create();
    }

    private School $school;

    public function test_a_document_is_downloaded_by_someone_who_may_view_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->createWithContent('report.pdf', self::CONTENTS),
        ])->assertSessionDoesntHaveErrors();

        $id = DB::table('documents')->value('id');

        $response = $this->get("/documents/{$id}/download");

        $response->assertOk();
        $this->assertSame(self::CONTENTS, $response->streamedContent());
    }

    public function test_the_download_names_the_file_rather_than_its_hash(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->createWithContent('report.pdf', self::CONTENTS),
        ]);

        $id = DB::table('documents')->value('id');
        $storedPath = (string) DB::table('documents')->value('file_path');

        $response = $this->get("/documents/{$id}/download");

        $disposition = (string) $response->headers->get('Content-Disposition');

        // The stored name is a random hash, so serving it hands the user
        // "a1b2c3d4.pdf" and tells them nothing about what they downloaded.
        $this->assertStringNotContainsString(
            basename($storedPath),
            $disposition,
            'The download is named after the stored hash, so the user is given a meaningless filename.',
        );
        $this->assertStringContainsString('term-one-report.pdf', $disposition);
    }

    public function test_a_material_is_downloaded_by_someone_who_may_view_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $this->post('/materials', [
            'title' => 'Week 1 lesson',
            'offering_id' => $this->offeringId(),
            'file' => UploadedFile::fake()->createWithContent('lesson.pdf', self::CONTENTS),
        ])->assertSessionDoesntHaveErrors();

        $id = DB::table('materials')->value('id');

        $response = $this->get("/materials/{$id}/download");

        $response->assertOk();
        $this->assertSame(self::CONTENTS, $response->streamedContent());
    }

    public function test_a_submission_is_downloaded_by_someone_who_may_view_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-submissions']);

        $this->post('/submissions', [
            'assignment_id' => $this->assignmentId(),
            'student_id' => Student::factory()->create(['school_id' => $this->school->id])->id,
            'file' => UploadedFile::fake()->createWithContent('homework.pdf', self::CONTENTS),
        ])->assertSessionDoesntHaveErrors();

        $id = DB::table('submissions')->value('id');

        $response = $this->get("/submissions/{$id}/download");

        $response->assertOk();
        $this->assertSame(self::CONTENTS, $response->streamedContent());
    }

    public function test_someone_in_another_school_cannot_download_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->createWithContent('report.pdf', self::CONTENTS),
        ]);

        $id = DB::table('documents')->value('id');

        $this->actingAsSchoolUser(School::factory()->create(), ['manage-documents']);

        // Scoped route binding answers 404 for a foreign row, which is the same
        // answer the record's own page gives. A download that answered 403 here
        // would confirm the document exists.
        $this->get("/documents/{$id}/download")->assertNotFound();
    }

    public function test_someone_without_the_permission_cannot_download_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->createWithContent('report.pdf', self::CONTENTS),
        ]);

        $id = DB::table('documents')->value('id');

        // Same school, so the tenant binding resolves and the permission is what
        // refuses. A user in another school 404s at the binding instead, which is
        // the other test.
        $this->actingAsSchoolUser($this->school, ['view-dashboard']);

        $this->get("/documents/{$id}/download")->assertForbidden();
    }

    public function test_a_stored_path_that_leaves_the_disk_is_refused(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        // No file is written outside the disk. Flysystem's path normaliser refuses
        // `../../` on its own — an attempt to create one throws
        // PathTraversalDetected during setup, which is a useful thing to know and
        // is why this guard is defence in depth. What the guard adds is the status
        // code: left to the exception, the download would surface as a 500, and
        // this route is the one place the application opens a file by a path held
        // in a database column.
        $id = DB::table('documents')->insertGetId([
            'school_id' => $this->school->id,
            'uploaded_by' => $this->school->id,
            'title' => 'Traversal attempt',
            'classification' => 'report',
            'file_path' => '../../.env',
            'file_size' => 10,
            'file_type' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // "Download this document" must not become "read any file the PHP
        // process can reach". The path lives in a column no user writes today,
        // which is exactly why this needs to be refused rather than assumed safe.
        $this->get("/documents/{$id}/download")->assertNotFound();
    }

    public function test_a_record_with_no_file_is_not_a_server_error(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-submissions']);

        $id = DB::table('submissions')->insertGetId([
            'school_id' => $this->school->id,
            'assignment_id' => $this->assignmentId(),
            'student_id' => Student::factory()->create(['school_id' => $this->school->id])->id,
            'content' => 'The answer is written out, there is no attachment.',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A submission with no attachment is an ordinary record, not a broken
        // one, so the route answers 404 rather than 500.
        $this->get("/submissions/{$id}/download")->assertNotFound();
    }

    // ------------------------------------------------------------------

    private function offeringId(): int
    {
        $teacherId = DB::table('teacher_profiles')->insertGetId([
            'school_id' => $this->school->id,
            'first_name' => 'Sami',
            'last_name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('offerings')->insertGetId([
            'school_id' => $this->school->id,
            'academic_year_id' => AcademicYear::factory()
                ->create(['school_id' => $this->school->id])->id,
            'subject_id' => Subject::factory()->create(['school_id' => $this->school->id])->id,
            'teacher_id' => $teacherId,
            'name' => 'Mathematics — Term 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignmentId(): int
    {
        return DB::table('assignments')->insertGetId([
            'school_id' => $this->school->id,
            'offering_id' => $this->offeringId(),
            'title' => 'Homework',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
