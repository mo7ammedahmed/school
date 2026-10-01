<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Learning\Models\Material;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploads stopped being written to the `public` disk, but every file already
 * there is still inside the document root and still reachable at a URL the web
 * server will serve.
 *
 * The fix was only half a fix until those files moved: the hole is closed for
 * new uploads and open for everything uploaded before it. This is the command
 * that closes the rest.
 *
 * The design point worth stating, because it is why this is safe to run against
 * production: **the stored path does not change.** A row says
 * `documents/abc.pdf` and the controller picks the disk, so relocating the file
 * to the same relative path on the private disk leaves every row valid and every
 * URL already sent out — a link somebody has in an email — still resolving to
 * the same record. There is no data migration here, and nothing to roll back
 * except the file move itself.
 */
class PublicUploadsAreRelocatedTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PATH = 'documents/legacy-report.pdf';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_a_document_left_on_the_public_disk_is_moved_onto_the_private_one(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        $this->artisan('uploads:relocate')->assertSuccessful();

        $this->assertTrue(
            Storage::disk('local')->exists(self::OLD_PATH),
            'The file is still only on the public disk, where the web server will serve it.',
        );
        $this->assertFalse(
            Storage::disk('public')->exists(self::OLD_PATH),
            'The copy inside the document root was left behind.',
        );
    }

    public function test_the_stored_path_does_not_change(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        $this->artisan('uploads:relocate')->assertSuccessful();

        // The whole reason this needs no migration: the path is relative and the
        // disk is chosen per operation, so the row is still correct afterwards.
        $this->assertSame(
            self::OLD_PATH,
            (string) DB::table('documents')->value('file_path'),
            'The path was rewritten. Links already sent to somebody would now point at nothing, and '
            .'every such row would need migrating.',
        );
    }

    public function test_a_material_and_a_submission_are_moved_too(): void
    {
        $this->documentStoredOn('documents/one.pdf');
        $this->materialStoredOn('materials/lesson.docx');
        $this->submissionStoredOn('submissions/homework.pdf');

        $this->artisan('uploads:relocate')->assertSuccessful();

        foreach (['documents/one.pdf', 'materials/lesson.docx', 'submissions/homework.pdf'] as $path) {
            $this->assertTrue(Storage::disk('local')->exists($path), "{$path} did not move.");
            $this->assertFalse(Storage::disk('public')->exists($path), "{$path} is still in the document root.");
        }
    }

    public function test_it_reports_what_it_moved(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        $this->artisan('uploads:relocate')
            ->expectsOutputToContain('1')
            ->assertSuccessful();
    }

    public function test_a_dry_run_moves_nothing(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        $this->artisan('uploads:relocate', ['--dry-run' => true])->assertSuccessful();

        $this->assertTrue(
            Storage::disk('public')->exists(self::OLD_PATH),
            'A dry run moved the file, so it cannot be used to see what would happen.',
        );
        $this->assertFalse(Storage::disk('local')->exists(self::OLD_PATH));
    }

    public function test_a_file_already_on_the_private_disk_is_not_overwritten(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        // Same path, different content: the upload was replaced after the disk
        // change and the newer file is the one that matters.
        Storage::disk('local')->put(self::OLD_PATH, 'the current version');

        $this->artisan('uploads:relocate')->assertSuccessful();

        $this->assertSame(
            'the current version',
            Storage::disk('local')->get(self::OLD_PATH),
            'The private copy was overwritten by a stale one from the public disk.',
        );
        $this->assertFalse(
            Storage::disk('public')->exists(self::OLD_PATH),
            'The stale copy inside the document root was left behind.',
        );
    }

    public function test_a_row_whose_file_is_missing_is_left_alone(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        Storage::disk('public')->delete(self::OLD_PATH);

        // No file, no move, and no invented state: the row keeps its path and
        // whatever else is wrong with it stays visible rather than being papered
        // over by a command that "succeeded".
        $this->artisan('uploads:relocate')->assertSuccessful();

        $this->assertSame(
            self::OLD_PATH,
            (string) DB::table('documents')->value('file_path'),
        );
    }

    public function test_it_relocates_across_every_school(): void
    {
        $this->documentStoredOn(self::OLD_PATH);

        // A console command has no tenant context, and the tenant scope's
        // deliberate answer to that is "no rows". A sweep that quietly saw zero
        // documents would report success and leave every school's files exposed.
        $this->assertSame(
            0,
            Document::query()->count(),
            'The tenant scope is no longer hiding rows from console work; this test needs rethinking.',
        );

        $this->artisan('uploads:relocate')->assertSuccessful();

        $this->assertTrue(Storage::disk('local')->exists(self::OLD_PATH));
    }

    // ------------------------------------------------------------------

    private function documentStoredOn(string $path): void
    {
        $schoolId = School::factory()->create()->id;

        Storage::disk('public')->put($path, '%PDF-1.4 the file as uploaded');

        DB::table('documents')->insert([
            'school_id' => $schoolId,
            'uploaded_by' => User::factory()->create()->id,
            'title' => 'Legacy report',
            'classification' => 'report',
            'file_path' => $path,
            'file_size' => 25,
            'file_type' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function materialStoredOn(string $path): void
    {
        $schoolId = School::factory()->create()->id;
        $offeringId = $this->offeringId($schoolId);

        Storage::disk('public')->put($path, 'the lesson plan');

        DB::table('materials')->insert([
            'school_id' => $schoolId,
            'offering_id' => $offeringId,
            'title' => 'Week 1',
            'file_path' => $path,
            'file_size' => 18,
            'file_type' => 'docx',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function submissionStoredOn(string $path): void
    {
        $schoolId = School::factory()->create()->id;
        $offeringId = $this->offeringId($schoolId);
        $studentId = Student::factory()->create(['school_id' => $schoolId])->id;

        $assignmentId = DB::table('assignments')->insertGetId([
            'school_id' => $schoolId,
            'offering_id' => $offeringId,
            'title' => 'Homework',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('public')->put($path, 'the homework');

        DB::table('submissions')->insert([
            'school_id' => $schoolId,
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
            'file_path' => $path,
            'file_size' => 12,
            'file_type' => 'pdf',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * `offerings` and `teacher_profiles` have no factory, so the rows that carry
     * the foreign keys are written directly.
     */
    private function offeringId(int $schoolId): int
    {
        $teacherId = DB::table('teacher_profiles')->insertGetId([
            'school_id' => $schoolId,
            'first_name' => 'Sami',
            'last_name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('offerings')->insertGetId([
            'school_id' => $schoolId,
            'academic_year_id' => AcademicYear::factory()->create(['school_id' => $schoolId])->id,
            'subject_id' => Subject::factory()->create(['school_id' => $schoolId])->id,
            'teacher_id' => $teacherId,
            'name' => 'Mathematics — Term 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}