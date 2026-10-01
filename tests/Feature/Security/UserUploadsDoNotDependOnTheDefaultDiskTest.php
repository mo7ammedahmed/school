<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Documents, materials and submissions were moved off the public disk when the
 * upload allowlist landed — but their writes read
 *
 *     $request->file('file')->store('documents')
 *
 * with no disk named. `store()` then falls back to `config('filesystems.default')`,
 * which is `FILESYSTEM_DISK`. The sibling admissions upload names its disk
 * explicitly (`store(..., 'local')`), so it was safe by construction; these three
 * were safe only because the environment happened to say `local`.
 *
 * `FILESYSTEM_DISK=public` is an ordinary thing for an operator to set — it is a
 * reasonable-looking setting for serving assets — and the moment it is set, every
 * user upload in the product goes back into the document root, where a `.php` file
 * is executed by the web server. Nothing in the code changes. The allowlist still
 * refuses `.php`, so the two controls are not independent, but the disk is the
 * control that was supposed to hold and it is held by a value in `.env`.
 *
 * The guarantee belongs in the code, not in the environment. These tests set the
 * default disk to `public` deliberately: the invariant is that the disk is named
 * at the call site, so the setting cannot change where a user's file lands.
 */
class UserUploadsDoNotDependOnTheDefaultDiskTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        Storage::fake('public');
        Storage::fake('local');

        // The deployment this guards against. Nothing in the application changes
        // when this value changes, so nothing in the application should change
        // behaviour when it does either.
        config(['filesystems.default' => 'public']);
    }

    public function test_a_document_does_not_land_in_the_document_root(): void
    {
        $this->storeDocument();

        $this->assertNothingInTheDocumentRoot('documents');
        $this->assertSomethingWasStored('documents');
    }

    public function test_a_material_does_not_land_in_the_document_root(): void
    {
        $this->storeMaterial();

        $this->assertNothingInTheDocumentRoot('materials');
        $this->assertSomethingWasStored('materials');
    }

    public function test_a_submission_does_not_land_in_the_document_root(): void
    {
        $this->storeSubmission();

        $this->assertNothingInTheDocumentRoot('submissions');
        $this->assertSomethingWasStored('submissions');
    }

    public function test_the_only_writes_to_the_public_disk_are_the_two_branding_images(): void
    {
        $publicWrites = [];

        foreach ($this->controllerSources() as $path => $source) {
            preg_match_all(
                '/->file\(\s*[\'"][^\'"]+[\'"]\s*\)\s*->store\([^;]*[\'"]public[\'"]\s*\)/',
                $source,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $publicWrites[] = basename($path).': '.preg_replace('/\s+/', ' ', trim($match[0]));
            }
        }

        sort($publicWrites);

        // These three stay on the public disk deliberately: a logo and a favicon
        // are pictures that the app shell renders by URL on every page, and giving
        // them signed URLs would mean a request per page for an image. Each is
        // restricted to raster types by its own validation, so a file the web
        // server would execute cannot be written there.
        //
        // There are two logo endpoints — the general school settings screen and
        // the appearance screen — which is the duplication worth noting: the same
        // setting is writable from two places, and they store to two different
        // directories. Both are listed here so that neither is accidental.
        //
        // A fourth entry is a finding, not a style change: everything else a user
        // uploads is a document, and a document does not belong in a web root.
        $this->assertSame(
            [
                'AppearanceSettingsController.php: ->file(\'favicon\')->store(\'school-branding\', \'public\')',
                'AppearanceSettingsController.php: ->file(\'logo\')->store(\'school-branding\', \'public\')',
                'SchoolSettingsController.php: ->file(\'logo\')->store(\'school-logos\', \'public\')',
            ],
            array_values(array_unique($publicWrites)),
        );
    }

    /**
     * Every user-upload write in the controllers, by disk argument.
     *
     * A source scan rather than a behavioural pass over each endpoint, because the
     * defect is the *absence* of a disk argument and that is not something a
     * request can demonstrate — with the default set to `public` it is invisible,
     * and with the default set to `local` it is invisible too.
     */
    public function test_no_controller_writes_a_user_upload_without_naming_its_disk(): void
    {
        $offenders = [];

        foreach ($this->controllerSources() as $path => $source) {
            // `->file('x')->store('dir')` and the `UploadedFile` method chain both
            // end at a `store()` call; only those reach the filesystem.
            preg_match_all(
                '/->file\(\s*[\'"][^\'"]+[\'"]\s*\)\s*->store\(([^;]*)\)/',
                $source,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $arguments = trim($match[1]);

                if (! str_contains($arguments, ',')) {
                    $offenders[] = $path.': ->store('.$arguments.') names no disk';
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "A user upload is written without naming a disk, so it lands wherever FILESYSTEM_DISK points:\n"
            .implode("\n", $offenders),
        );
    }

    // ------------------------------------------------------------------

    /**
     * Nothing of the user's is sitting where the web server can serve it.
     */
    private function assertNothingInTheDocumentRoot(string $directory): void
    {
        $exposed = Storage::disk('public')->allFiles($directory);

        $this->assertSame(
            [],
            $exposed,
            'A user upload was written to the public disk, where storage:link publishes it inside the '
            .'document root. The write did not name a disk, so where the file lands is decided by '
            .'FILESYSTEM_DISK rather than by this code.',
        );
    }

    /**
     * The upload was accepted and stored — a test that only proves nothing was
     * exposed would also pass against an endpoint that refuses everything.
     *
     * The stored name is a random hash, so the directory is what is asserted.
     */
    private function assertSomethingWasStored(string $directory): void
    {
        $this->assertNotEmpty(
            Storage::disk('local')->allFiles($directory),
            "Nothing was stored under {$directory}/, so this test never reached the disk choice it is "
            .'supposed to be about.',
        );
    }

    private function storeDocument(): void
    {
        $this->actingAs($this->schoolUser(['manage-documents']));

        $this->post('/documents', [
            'title' => 'Term one report',
            'classification' => 'report',
            'file' => UploadedFile::fake()->create('term-one.pdf', 120, 'application/pdf'),
        ])->assertSessionDoesntHaveErrors();

        $this->assertNotNull(
            DB::table('documents')->value('file_path'),
            'No document row was written, so this test never reached the disk choice it is supposed to be about.',
        );
    }

    private function storeMaterial(): void
    {
        $this->actingAs($this->schoolUser(['manage-materials']));

        $this->post('/materials', [
            'title' => 'Week 1 lesson',
            'offering_id' => $this->offeringId(),
            'file' => UploadedFile::fake()->create('week-one.pdf', 120, 'application/pdf'),
        ])->assertSessionDoesntHaveErrors();

        $this->assertNotNull(
            DB::table('materials')->value('file_path'),
            'No material row was written, so this test never reached the disk choice it is supposed to be about.',
        );
    }

    private function storeSubmission(): void
    {
        $this->actingAs($this->schoolUser(['manage-submissions']));

        $this->post('/submissions', [
            'assignment_id' => $this->assignmentId(),
            'student_id' => Student::factory()->create(['school_id' => $this->school->id])->id,
            'content' => 'See attached.',
            'file' => UploadedFile::fake()->create('homework.pdf', 120, 'application/pdf'),
        ])->assertSessionDoesntHaveErrors();

        $this->assertNotNull(
            DB::table('submissions')->value('file_path'),
            'No submission row was written, so this test never reached the disk choice it is supposed to be about.',
        );
    }

    private function schoolUser(array $permissions): User
    {
        return $this->actingAsSchoolUser($this->school, $permissions);
    }

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
            'subject_id' => Subject::factory()
                ->create(['school_id' => $this->school->id])->id,
            'teacher_id' => $teacherId,
            'name' => 'Mathematics — Term 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignmentId(): int
    {
        $student = Student::factory()->create(['school_id' => $this->school->id]);

        return DB::table('assignments')->insertGetId([
            'school_id' => $this->school->id,
            'offering_id' => $this->offeringId(),
            'title' => 'Homework',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function controllerSources(): array
    {
        $sources = [];

        foreach (glob(app_path('Http/Controllers/**/*Controller.php')) ?: [] as $path) {
            $sources[$path] = (string) file_get_contents($path);
        }

        $this->assertNotEmpty($sources, 'No controllers were found, so this scan proved nothing.');

        return $sources;
    }
}
