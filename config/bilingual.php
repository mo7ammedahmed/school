<?php

declare(strict_types=1);

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Enrollment;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\GradingScale;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Models\AdmissionApplicationEvent;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\AssessmentScore;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Communication\Models\Announcement;
use App\Domain\Communication\Models\Message;
use App\Domain\Communication\Models\Notification;
use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Models\Event;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\News;
use App\Domain\Content\Models\StaffProfile;
use App\Domain\Content\Models\WebsiteNavigationItem;
use App\Domain\Content\Models\WebsiteNavigationMenu;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Finance\Models\Discount;
use App\Domain\Finance\Models\FeeAssignment;
use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Finance\Models\FeeType;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\InvoiceLine;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Material;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Learning\Models\Submission;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Scheduling\Models\CalendarDay;
use App\Domain\Scheduling\Models\Period;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Schools\Models\Organization;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolNavigationLabel;

return [

    /*
    |--------------------------------------------------------------------------
    | Bilingual targets
    |--------------------------------------------------------------------------
    |
    | Every table that stores content in an English and an Arabic column, so the
    | system knows what "translate everything that is missing" has to cover.
    |
    | Each entry declares:
    |
    |   label           what the operator sees in the report
    |   model           the Eloquent model to sweep
    |   scope           the column holding the school id (default `school_id`)
    |   scope_value     'school' (default) or 'organization' — where the value
    |                   compared against `scope` comes from
    |   scope_relation  instead of a column, scope through this relation, which
    |                   carries the school id itself
    |   pairs           English column => Arabic column pairs, filled both ways
    |
    | Adding a bilingual column to a new table means adding it here; nothing else
    | in the backfill needs to change. `tests/Feature/Localization/
    | BilingualSchemaTest.php` fails if an entry names a column that does not
    | exist, so the list cannot drift from the schema.
    |
    | Not listed: the website_pages, website_sections, website_media and
    | website_theme_presets tables have `*_ar` columns but no model yet, so they
    | are out of reach of the automatic sweep until one is added.
    |
    */

    'targets' => [

        // ------------------------------------------------------------------
        // Branding
        // ------------------------------------------------------------------

        [
            'label' => 'School name',
            'model' => School::class,
            'scope' => 'id',
            'pairs' => [
                ['en' => 'name_en', 'ar' => 'name_ar'],
                ['en' => 'description_en', 'ar' => 'description_ar'],
                ['en' => 'address', 'ar' => 'address_ar'],
            ],
        ],

        [
            'label' => 'Organisation name',
            'model' => Organization::class,
            'scope' => 'id',
            'scope_value' => 'organization',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'address', 'ar' => 'address_ar']],
        ],

        // ------------------------------------------------------------------
        // Academics
        // ------------------------------------------------------------------

        [
            'label' => 'Academic years',
            'model' => AcademicYear::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar']],
        ],

        [
            'label' => 'Semesters',
            'model' => Semester::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar']],
        ],

        [
            'label' => 'Grade levels',
            'model' => GradeLevel::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'name_en', 'ar' => 'name_ar'],
                ['en' => 'description', 'ar' => 'description_ar'],
            ],
        ],

        [
            'label' => 'Sections',
            'model' => Section::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar'], ['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        [
            'label' => 'Subjects',
            'model' => Subject::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Rooms',
            'model' => Room::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Periods',
            'model' => Period::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar']],
        ],

        [
            'label' => 'Course offerings',
            'model' => Offering::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Enrolments',
            'model' => Enrollment::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        [
            'label' => 'Grading categories',
            'model' => GradingCategory::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Grading scales',
            'model' => GradingScale::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'School calendar',
            'model' => CalendarDay::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        // ------------------------------------------------------------------
        // People
        // ------------------------------------------------------------------

        [
            'label' => 'Staff profiles',
            'model' => StaffProfile::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'position', 'ar' => 'position_ar'],
                ['en' => 'department', 'ar' => 'department_ar'],
                ['en' => 'bio', 'ar' => 'bio_ar'],
            ],
        ],

        [
            'label' => 'Teacher profiles',
            'model' => TeacherProfile::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'qualification', 'ar' => 'qualification_ar'],
                ['en' => 'specialization', 'ar' => 'specialization_ar'],
                ['en' => 'bio', 'ar' => 'bio_ar'],
            ],
        ],

        [
            'label' => 'Student records',
            'model' => Student::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'address', 'ar' => 'address_ar'],
                ['en' => 'medical_notes', 'ar' => 'medical_notes_ar'],
            ],
        ],

        [
            'label' => 'Guardian relationships',
            'model' => GuardianRelationship::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        // ------------------------------------------------------------------
        // Admissions
        // ------------------------------------------------------------------

        [
            'label' => 'Admission periods',
            'model' => AdmissionPeriod::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Admission applications',
            'model' => AdmissionApplication::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'grade_applying', 'ar' => 'grade_applying_ar'],
                ['en' => 'student_notes', 'ar' => 'student_notes_ar'],
                ['en' => 'review_notes', 'ar' => 'review_notes_ar'],
            ],
        ],

        [
            'label' => 'Admission timeline notes',
            'model' => AdmissionApplicationEvent::class,
            'scope_relation' => 'application',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        // ------------------------------------------------------------------
        // Communication
        // ------------------------------------------------------------------

        [
            'label' => 'Announcements',
            'model' => Announcement::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar'], ['en' => 'body', 'ar' => 'body_ar']],
        ],

        [
            'label' => 'Messages',
            'model' => Message::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'body', 'ar' => 'body_ar']],
        ],

        [
            'label' => 'Notifications',
            'model' => Notification::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar'], ['en' => 'body', 'ar' => 'body_ar']],
        ],

        // ------------------------------------------------------------------
        // Website content
        // ------------------------------------------------------------------

        [
            'label' => 'Website events',
            'model' => Event::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'title', 'ar' => 'title_ar'],
                ['en' => 'description', 'ar' => 'description_ar'],
                ['en' => 'location', 'ar' => 'location_ar'],
            ],
        ],

        [
            'label' => 'News posts',
            'model' => News::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'title', 'ar' => 'title_ar'],
                ['en' => 'excerpt', 'ar' => 'excerpt_ar'],
                ['en' => 'content', 'ar' => 'content_ar'],
            ],
        ],

        [
            'label' => 'FAQs',
            'model' => Faq::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'question', 'ar' => 'question_ar'], ['en' => 'answer', 'ar' => 'answer_ar']],
        ],

        [
            'label' => 'Content pages',
            'model' => ContentPage::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar']],
        ],

        [
            'label' => 'Website menus',
            'model' => WebsiteNavigationMenu::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar']],
        ],

        [
            'label' => 'Website menu items',
            'model' => WebsiteNavigationItem::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'label_en', 'ar' => 'label_ar']],
        ],

        [
            'label' => 'Dashboard navigation labels',
            'model' => SchoolNavigationLabel::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name_en', 'ar' => 'name_ar']],
        ],

        // ------------------------------------------------------------------
        // Documents and learning material
        // ------------------------------------------------------------------

        [
            'label' => 'Documents',
            'model' => Document::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Document categories',
            'model' => DocumentCategory::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Learning materials',
            'model' => Material::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Assignments',
            'model' => Assignment::class,
            'scope' => 'school_id',
            'pairs' => [
                ['en' => 'title', 'ar' => 'title_ar'],
                ['en' => 'description', 'ar' => 'description_ar'],
                ['en' => 'instructions', 'ar' => 'instructions_ar'],
            ],
        ],

        [
            'label' => 'Quizzes',
            'model' => Quiz::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'title', 'ar' => 'title_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Submission feedback',
            'model' => Submission::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'feedback', 'ar' => 'feedback_ar']],
        ],

        // ------------------------------------------------------------------
        // Assessment
        // ------------------------------------------------------------------

        [
            'label' => 'Assessments',
            'model' => Assessment::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Exams',
            'model' => Exam::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Assessment feedback',
            'model' => AssessmentScore::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'feedback', 'ar' => 'feedback_ar']],
        ],

        [
            'label' => 'Exam results',
            'model' => ExamResult::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        [
            'label' => 'Report card comments',
            'model' => ReportCard::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'comments', 'ar' => 'comments_ar']],
        ],

        [
            'label' => 'Attendance notes',
            'model' => AttendanceRecord::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        // ------------------------------------------------------------------
        // Finance
        // ------------------------------------------------------------------

        [
            'label' => 'Fee types',
            'model' => FeeType::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Fee structures',
            'model' => FeeStructure::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Discounts',
            'model' => Discount::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'name', 'ar' => 'name_ar'], ['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Fee assignments',
            'model' => FeeAssignment::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        [
            'label' => 'Invoices',
            'model' => Invoice::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        [
            'label' => 'Invoice lines',
            'model' => InvoiceLine::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'description', 'ar' => 'description_ar']],
        ],

        [
            'label' => 'Payments',
            'model' => Payment::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'notes', 'ar' => 'notes_ar']],
        ],

        [
            'label' => 'Refunds',
            'model' => Refund::class,
            'scope' => 'school_id',
            'pairs' => [['en' => 'reason', 'ar' => 'reason_ar']],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Run budget
    |--------------------------------------------------------------------------
    |
    | Each translated value is a paid provider call, so a single run stops after
    | this many translations and reports what is left. Operators can press the
    | button again to continue where the run stopped, and the settings screen
    | walks the sweep in smaller batches of its own so progress is visible.
    |
    */

    'max_translations_per_run' => (int) env('TRANSLATION_BACKFILL_LIMIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Run clock
    |--------------------------------------------------------------------------
    |
    | Every translated value is a provider call, so a run is stopped by two
    | clocks rather than one:
    |
    |   * `max_seconds_per_run` is the soft one. Past it the run stops starting
    |     calls and reports what is left, so a request comes back while the screen
    |     can still show progress.
    |   * PHP's own `max_execution_time` is the hard one. A call is only begun
    |     while `request_timeout_seconds` still fits inside it, because the fatal
    |     "Maximum execution time of 30 seconds exceeded" killed the request mid
    |     call, wrote nothing, and left the screen on "Translating…" for ever.
    |
    | Both are read live (the CLI reports no limit while the server that serves
    | the app enforces 30s), and on a tighter host the per-call ceiling is
    | shortened to fit instead of being left to be killed. The screen then walks
    | the backlog in further batches.
    |
    */

    'max_seconds_per_run' => (float) env('TRANSLATION_RUN_SECONDS', 16),

    'request_timeout_seconds' => (int) env('TRANSLATION_REQUEST_SECONDS', 10),

    /*
    |--------------------------------------------------------------------------
    | Translation cache
    |--------------------------------------------------------------------------
    |
    | Identical source text is translated once and reused for this many days,
    | so a school with 96 offerings but eight distinct names pays for eight
    | translations. The cache key includes the provider and model, so changing
    | either re-translates instead of serving the previous wording. Set to 0 to
    | always call the provider.
    |
    */

    'translation_cache_days' => (int) env('TRANSLATION_CACHE_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Translate on save
    |--------------------------------------------------------------------------
    |
    | Saving a record fills whichever side is empty, whichever page or action
    | wrote it — the observer is attached to every target above, so the pages
    | that were never wired up individually translate too.
    |
    | `on_save_seconds` bounds that: an import saving a thousand rows stops
    | calling the provider once this much time has gone and leaves the rest to
    | the sweep above, which is budgeted and reports what it did. Filling runs
    | during requests only; commands and imports (and the test suite) leave the
    | work to the sweep unless `autofill_in_console` is turned on.
    |
    */

    'on_save_seconds' => (float) env('TRANSLATION_ON_SAVE_SECONDS', 6),

    'autofill_in_console' => (bool) env('TRANSLATION_AUTOFILL_IN_CONSOLE', false),

];
