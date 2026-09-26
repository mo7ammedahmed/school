<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every table that stores human-readable content an Arabic column beside
 * its English one.
 *
 * Convention: the existing column stays exactly where it is and holds the
 * English text; `{column}_ar` holds the Arabic. This mirrors the `title` /
 * `title_ar` pair on content pages and, unlike the older academic-tables
 * migration, never renames or drops a column, so no query has to change.
 *
 * The Arabic columns are deliberately left NULL rather than copied from
 * English: the Translations screen (Settings -> Translations -> "Test all
 * missing Arabic & English") finds and fills every empty side, so nothing is
 * silently presented as Arabic when it is not.
 *
 * Deliberately excluded: identifiers, codes, slugs, emails, phones, URLs,
 * enum-like status/gender/nationality columns, JSON blobs, audit/cache/queue
 * tables, and people's own names.
 */
return new class extends Migration
{
    /**
     * Table => [column => storage type], where the type mirrors the source
     * column so Arabic text is never truncated to 255 characters.
     *
     * @var array<string, array<string, 'string'|'text'>>
     */
    private const COLUMNS = [
        'admission_application_events' => ['notes' => 'text'],
        'admission_applications' => [
            'grade_applying' => 'string',
            'student_notes' => 'text',
            'review_notes' => 'text',
        ],
        'admission_periods' => ['name' => 'string', 'description' => 'text'],
        'announcements' => ['title' => 'string', 'body' => 'text'],
        'assessment_scores' => ['feedback' => 'text'],
        'assessments' => ['name' => 'string', 'description' => 'text'],
        'assignments' => ['title' => 'string', 'description' => 'text', 'instructions' => 'text'],
        'attendance_records' => ['notes' => 'text'],
        'calendar_days' => ['title' => 'string', 'description' => 'text'],
        'discounts' => ['name' => 'string', 'description' => 'text'],
        'document_categories' => ['name' => 'string', 'description' => 'text'],
        'documents' => ['title' => 'string', 'description' => 'text'],
        'enrollments' => ['notes' => 'text'],
        'events' => ['title' => 'string', 'description' => 'text', 'location' => 'string'],
        'exam_results' => ['notes' => 'text'],
        'exams' => ['name' => 'string', 'description' => 'text'],
        'faqs' => ['question' => 'string', 'answer' => 'text'],
        'fee_assignments' => ['notes' => 'text'],
        'fee_structures' => ['description' => 'text'],
        'fee_types' => ['name' => 'string', 'description' => 'text'],
        'grade_levels' => ['description' => 'text'],
        'grading_categories' => ['name' => 'string', 'description' => 'text'],
        'grading_scales' => ['name' => 'string', 'description' => 'text'],
        'guardian_relationships' => ['notes' => 'text'],
        'invoice_lines' => ['description' => 'string'],
        'invoices' => ['notes' => 'text'],
        'materials' => ['title' => 'string', 'description' => 'text'],
        'messages' => ['body' => 'text'],
        'news' => ['title' => 'string', 'excerpt' => 'text', 'content' => 'text'],
        'notifications' => ['title' => 'string', 'body' => 'text'],
        'offerings' => ['name' => 'string', 'description' => 'text'],
        'organizations' => ['name' => 'string', 'address' => 'text'],
        'payments' => ['notes' => 'text'],
        'quizzes' => ['title' => 'string', 'description' => 'text'],
        'refunds' => ['reason' => 'string'],
        'report_cards' => ['comments' => 'text'],
        'rooms' => ['description' => 'text'],
        'schools' => ['address' => 'text'],
        'sections' => ['notes' => 'text'],
        'staff_profiles' => ['position' => 'string', 'department' => 'string', 'bio' => 'text'],
        'students' => ['address' => 'text', 'medical_notes' => 'text'],
        'subjects' => ['description' => 'text'],
        'submissions' => ['feedback' => 'text'],
        'teacher_profiles' => [
            'qualification' => 'string',
            'bio' => 'text',
            'specialization' => 'string',
        ],
        'website_media' => ['name' => 'string', 'alt_text' => 'string', 'caption' => 'string'],
        'website_pages' => ['title' => 'text', 'description' => 'text'],
        'website_sections' => ['name' => 'string', 'content' => 'text'],
        'website_theme_presets' => ['name' => 'string', 'description' => 'text'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column => $type) {
                    if (Schema::hasColumn($table, $column.'_ar')) {
                        continue;
                    }

                    $definition = $type === 'text'
                        ? $blueprint->text($column.'_ar')
                        : $blueprint->string($column.'_ar');

                    $definition->nullable()->after($column);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = array_values(array_filter(
                array_map(static fn (string $column): string => $column.'_ar', array_keys($columns)),
                static fn (string $column): bool => Schema::hasColumn($table, $column),
            ));

            if ($existing === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($existing): void {
                $blueprint->dropColumn($existing);
            });
        }
    }
};
