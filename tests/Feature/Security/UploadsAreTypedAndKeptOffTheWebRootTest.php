<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Learning\Models\Material;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Materials, documents and submissions accepted `'file' => 'file|max:10240'`
 * and stored the result on the `public` disk.
 *
 * `file` asserts that a file was uploaded. It says nothing about what — a `.php`
 * passes it as readily as a `.pdf`. And `store('materials', 'public')` writes
 * into `storage/app/public`, which `php artisan storage:link` exposes inside the
 * document root as `/storage/materials/<name>.php`. On an ordinary nginx + PHP-FPM
 * or Apache deployment, requesting that URL *runs* the file.
 *
 * So this was not "someone can attach the wrong spreadsheet". Any account
 * holding `manage-materials`, `manage-documents` or `manage-submissions` could
 * place an arbitrary file where the web server will execute it. The stored name is
 * a Laravel random hash, which is obscurity rather than a control: the uploader is
 * shown its own `file_path`, and any list screen that renders one hands the path to
 * whoever is reading it.
 *
 * Two independent things are pinned, because either alone leaves a way in:
 *
 *   1. the *type* of file accepted, and
 *   2. the *disk* it lands on.
 *
 * A `.php` refused by an extension rule is safe only while the disk stays
 * meaningful to an attacker, and a private disk is safe only for as long as
 * nobody finds a type the rule missed — `.phtml`, `.svg`, `.html` are all things an
 * allowlist written once and forgotten will eventually let through.
 */
class UploadsAreTypedAndKeptOffTheWebRootTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        Storage::fake('public');
        Storage::fake('local');
    }

    // ------------------------------------------------------------------
    // 1. The type of file accepted.
    // ------------------------------------------------------------------

    public function test_a_material_cannot_be_a_php_file(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $this->post('/materials', $this->materialPayload($this->phpFile()))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Material::query()->count());
    }

    public function test_a_material_cannot_be_an_html_file(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        // An HTML file served from the application's own origin is a phishing page
        // that passes every same-origin check a browser makes.
        $this->post('/materials', $this->materialPayload(
            UploadedFile::fake()->createWithContent('statement.html', '<h1>Bank details changed</h1>')
        ))->assertSessionHasErrors('file');

        $this->assertSame(0, Material::query()->count());
    }

    public function test_a_double_extension_cannot_smuggle_a_php_file_past_the_rule(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        // `report.pdf.php` is the classic bypass for a rule that reads only the
        // last extension, and it is what a web server's own handler keys on.
        $this->post('/materials', $this->materialPayload(
            UploadedFile::fake()->createWithContent('term-dates.pdf.php', '<?php echo 1;')
        ))->assertSessionHasErrors('file');

        $this->assertSame(0, Material::query()->count());
    }

    public function test_a_document_cannot_be_a_php_file(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', $this->documentPayload($this->phpFile()))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_a_submission_cannot_be_a_php_file(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-submissions']);

        $this->post('/submissions', [
            'assignment_id' => 0,
            'student_id' => Student::factory()->create(['school_id' => $this->school->id])->id,
            'file' => $this->phpFile(),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_a_submission_pdf_is_not_rejected_for_its_type(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-submissions']);

        // `assignment_id => 0` is not a real assignment, so this request is
        // refused for that. What is being checked is narrower: the file rule did
        // not object to a PDF. Asserting on the `file` key alone is what makes
        // that legible — a blanket assertOk() would pass on a rule that refuses
        // everything.
        $this->post('/submissions', [
            'assignment_id' => 0,
            'student_id' => Student::factory()->create(['school_id' => $this->school->id])->id,
            'file' => UploadedFile::fake()->create('homework.pdf', 120, 'application/pdf'),
        ])->assertSessionDoesntHaveErrors('file');
    }

    public function test_an_allowed_document_type_is_still_accepted(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', $this->documentPayload(
            UploadedFile::fake()->create('term-dates.pdf', 120, 'application/pdf')
        ))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('documents', 1);
    }

    public function test_an_allowed_material_type_is_still_accepted(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $this->post('/materials', $this->materialPayload(
            UploadedFile::fake()->create('lesson.docx', 120, self::DOCX_MIME)
        ))->assertSessionHasNoErrors();

        $this->assertSame(1, Material::query()->count());
    }

    // ------------------------------------------------------------------
    // 2. The disk it lands on.
    // ------------------------------------------------------------------

    public function test_an_uploaded_material_is_not_left_where_the_web_server_serves_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $this->post('/materials', $this->materialPayload(
            UploadedFile::fake()->create('lesson.docx', 120, self::DOCX_MIME)
        ));

        $path = Material::query()->firstOrFail()->file_path;

        $this->assertFalse(
            Storage::disk('public')->exists($path),
            "The material was written to the public disk at {$path}. Anything on that disk is reachable "
            .'at a URL the web server will serve, and will run if it is PHP.',
        );
    }

    public function test_an_uploaded_document_is_not_left_where_the_web_server_serves_it(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-documents']);

        $this->post('/documents', $this->documentPayload(
            UploadedFile::fake()->create('term-dates.pdf', 120, 'application/pdf')
        ));

        $path = (string) DB::table('documents')->value('file_path');

        $this->assertFalse(
            Storage::disk('public')->exists($path),
            "The document was written to the public disk at {$path}.",
        );
    }

    public function test_a_typed_upload_still_lands_somewhere(): void
    {
        $this->actingAsSchoolUser($this->school, ['manage-materials']);

        $this->post('/materials', $this->materialPayload(
            UploadedFile::fake()->create('lesson.docx', 120, self::DOCX_MIME)
        ));

        $path = Material::query()->firstOrFail()->file_path;

        // The disk change is not "stop storing files". A path that is recorded but
        // never written is a record of nothing.
        $this->assertTrue(
            Storage::disk('local')->exists($path),
            "The material was recorded at {$path} but no file was written there.",
        );
    }

    // ------------------------------------------------------------------

    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    private function phpFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('shell.php', '<?php echo "owned";');
    }

    /**
     * @return array<string, mixed>
     */
    private function documentPayload(UploadedFile $file): array
    {
        return [
            'title' => 'Term dates',
            'classification' => 'policy',
            'file' => $file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function materialPayload(UploadedFile $file): array
    {
        return [
            'title' => 'Week 1 lesson',
            'offering_id' => $this->offeringId(),
            'file' => $file,
        ];
    }

    /**
     * An active offering for this school.
     *
     * The materials screen posts an offering rather than a subject and section,
     * and the controller refuses the pair when no active offering resolves to
     * one. Neither `offerings` nor `teacher_profiles` has a factory, so the two
     * rows are written directly — a test helper that exists only to satisfy a
     * foreign key is not worth a factory.
     */
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
            'academic_year_id' => AcademicYear::factory()->create(['school_id' => $this->school->id])->id,
            'subject_id' => Subject::factory()->create(['school_id' => $this->school->id])->id,
            'teacher_id' => $teacherId,
            'section_id' => Section::factory()->create(['school_id' => $this->school->id])->id,
            'name' => 'Mathematics — Term 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
