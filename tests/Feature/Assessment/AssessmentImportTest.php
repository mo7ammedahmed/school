<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Services\DocxQuestionParser;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Learning\Models\Quiz;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssessmentImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Offering $offering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);

        $gradeLevel = GradeLevel::create([
            'school_id' => $this->school->id,
            'name_en' => 'Grade 7',
            'code' => 'G7',
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name_en' => 'Grade 7A',
            'code' => 'G7A',
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name_en' => 'Physics',
            'name_ar' => 'الفيزياء',
            'code' => 'PHY',
        ]);

        $teacher = TeacherProfile::create([
            'school_id' => $this->school->id,
            'first_name' => 'Hana',
            'last_name' => 'Yousef',
        ]);

        $this->offering = Offering::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'name' => 'Physics · Grade 7A',
        ]);
    }

    public function test_uploading_a_document_returns_a_parsed_preview(): void
    {
        $this->actingAsTeacher();

        $response = $this->from('/assessments/import')->post('/assessments/import/parse', [
            'type' => 'exam',
            'offering_id' => $this->offering->id,
            'document' => $this->docx(),
        ]);

        $response->assertRedirect('/assessments/import');
        $response->assertSessionHasNoErrors();

        $preview = session('import_preview');

        $this->assertSame('Example Chapter Quiz', $preview['name']);
        $this->assertCount(3, $preview['questions']);
        $this->assertSame('multiple_choice', $preview['questions'][0]['type']);
        $this->assertSame([], $preview['warnings']);
    }

    public function test_a_non_docx_upload_is_rejected(): void
    {
        $this->actingAsTeacher();

        $this->from('/assessments/import')
            ->post('/assessments/import/parse', [
                'type' => 'exam',
                'offering_id' => $this->offering->id,
                'document' => UploadedFile::fake()->create('questions.txt', 4, 'text/plain'),
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_confirming_creates_an_exam_holding_the_questions(): void
    {
        $this->actingAsTeacher();

        $this->post('/assessments/import', [
            'type' => 'exam',
            'offering_id' => $this->offering->id,
            'name' => 'Imported Midterm',
            'exam_date' => '2026-10-05',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'questions' => $this->questions(),
        ])->assertRedirect();

        $exam = Exam::first();

        $this->assertNotNull($exam);
        $this->assertSame('Imported Midterm', $exam->name);
        $this->assertSame($this->school->id, $exam->school_id);
        $this->assertSame('2026-10-05', $exam->exam_date->toDateString());
        $this->assertCount(3, $exam->questions);
        $this->assertSame('B', $exam->questions[0]['answer']);
        // max_score falls back to the sum of the question points.
        $this->assertSame('6.00', $exam->max_score);
        $this->assertFalse((bool) $exam->is_published);
    }

    public function test_confirming_can_create_a_quiz_instead(): void
    {
        $this->actingAsTeacher();

        $this->post('/assessments/import', [
            'type' => 'quiz',
            'offering_id' => $this->offering->id,
            'name' => 'Imported Pop Quiz',
            'time_limit_minutes' => 15,
            'questions' => $this->questions(),
        ])->assertRedirect();

        $quiz = Quiz::first();

        $this->assertNotNull($quiz);
        $this->assertSame('Imported Pop Quiz', $quiz->title);
        $this->assertSame(15, $quiz->time_limit_minutes);
        $this->assertCount(3, $quiz->questions);
        $this->assertSame(0, Exam::count());
    }

    public function test_an_import_with_no_questions_is_rejected(): void
    {
        $this->actingAsTeacher();

        $this->post('/assessments/import', [
            'type' => 'exam',
            'offering_id' => $this->offering->id,
            'name' => 'Empty',
            'exam_date' => '2026-10-05',
            'questions' => [],
        ])->assertSessionHasErrors('questions');
    }

    public function test_an_unprivileged_user_cannot_import(): void
    {
        $user = User::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $this->school->id);

        $this->get('/assessments/import')->assertForbidden();
    }

    public function test_the_page_renders_for_a_teacher(): void
    {
        $this->actingAsTeacher();

        $this->get('/assessments/import')->assertOk();
    }

    /**
     * A real .docx, generated by the parser's own template builder.
     */
    private function docx(): UploadedFile
    {
        $path = (new DocxQuestionParser)->buildTemplate();
        $copy = $path.'.upload.docx';
        copy($path, $copy);
        @unlink($path);

        return new UploadedFile(
            $copy,
            'question-paper.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function questions(): array
    {
        return [
            [
                'type' => 'multiple_choice',
                'prompt' => 'What is 2 + 2?',
                'options' => [
                    ['key' => 'A', 'text' => '3'],
                    ['key' => 'B', 'text' => '4'],
                ],
                'answer' => 'B',
                'points' => 2,
            ],
            [
                'type' => 'true_false',
                'prompt' => 'Water boils at 100°C at sea level.',
                'options' => [
                    ['key' => 'A', 'text' => 'True'],
                    ['key' => 'B', 'text' => 'False'],
                ],
                'answer' => 'A',
                'points' => 1,
            ],
            [
                'type' => 'short_answer',
                'prompt' => 'Name the process plants use to make food.',
                'options' => [],
                'answer' => 'Photosynthesis',
                'points' => 3,
            ],
        ];
    }

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'manage-exams', 'guard_name' => 'web']);
        $user->givePermissionTo('manage-exams');

        $this->actingAs($user);
        $this->app['session']->put('school_id', $this->school->id);

        return $user;
    }
}
