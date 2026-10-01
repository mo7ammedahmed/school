<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public faculty list carefully chooses what a visitor may see: eight named
 * columns, no more. The public single-teacher page returns the whole row instead,
 * so it carries everything the list withholds.
 *
 * The extra columns are an HR record and a set of internal identifiers:
 * `employee_id` and `hire_date` describe a person's employment, `metadata` is a
 * free-form JSON column nothing constrains what goes in it, and `user_id` ties a
 * public staff profile to a login account — which is the one that matters most,
 * because it is a join key from an unauthenticated page to somebody's credentials.
 *
 * `PublicSiteTest` already covers this page rendering correctly, and it passes:
 * the test checks what the page *has* and never what it *should not*, which is
 * how a superset of a carefully chosen field set goes unnoticed.
 */
class PublicStaffPagesDoNotLeakInternalColumnsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * What the list already publishes, and therefore the whole public surface.
     *
     * Asserted as a set rather than as a list of forbidden keys, so a column added
     * to the model later cannot quietly appear on this page.
     *
     * @var list<string>
     */
    private const PUBLIC_COLUMNS = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'specialization',
        'bio',
        'qualification',
    ];

    public function test_a_visitor_cannot_see_a_teachers_employment_record(): void
    {
        $teacher = $this->teacher();

        $this->get("/faculty/{$teacher->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/teachers/show')
            ->missing('teacher.employee_id')
            ->missing('teacher.hire_date')
        );
    }

    public function test_a_visitor_cannot_tell_which_login_a_teacher_uses(): void
    {
        $teacher = $this->teacher();

        $this->get("/faculty/{$teacher->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->missing('teacher.user_id')
        );
    }

    public function test_a_visitor_cannot_read_the_unconstrained_metadata_column(): void
    {
        $teacher = $this->teacher();

        $this->get("/faculty/{$teacher->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->missing('teacher.metadata')
        );
    }

    public function test_the_profile_page_carries_exactly_the_fields_the_list_publishes(): void
    {
        $teacher = $this->teacher();

        $this->get("/faculty/{$teacher->id}")->assertOk()->assertInertia(function ($page): void {
            $teacher = $page->toArray()['props']['teacher'] ?? $page->toArray()['teacher'];

            // `subjects` is the one addition, and it is the reason the page exists.
            unset($teacher['subjects']);

            $this->assertSame(
                $this->sorted(self::PUBLIC_COLUMNS),
                $this->sorted(array_keys($teacher)),
                'The public profile page carries columns the faculty list deliberately withholds, or is '
                .'missing one it publishes. The two pages should agree on the public field set.',
            );
        });
    }

    public function test_the_list_still_publishes_what_it_always_did(): void
    {
        $this->teacher();

        // The guard above is a set equality, which would also pass if the list
        // quietly dropped everything. This is the half that says nothing regressed.
        $this->get('/faculty')->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/teachers')
            ->where('teachers.0.first_name', 'Sarah')
            ->where('teachers.0.specialization', 'Mathematics')
        );
    }

    // ------------------------------------------------------------------

    private function teacher(): TeacherProfile
    {
        $school = School::factory()->create();

        return TeacherProfile::factory()->create([
            'school_id' => $school->id,
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'specialization' => 'Mathematics',
            'employee_id' => 'EMP-0042',
            'hire_date' => '2019-03-01',
            'metadata' => ['internal_note' => 'contract ends 2027-06'],
        ]);
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
