<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guards the sidebar against link rot: every href the shell renders for an
 * administrator must resolve to a real page.
 *
 * The list mirrors NAV_GROUPS in resources/js/layouts/app-shell.tsx. Role
 * portals (student/guardian) are intentionally absent because they belong to
 * other roles.
 */
class SidebarLinksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminLinks(): array
    {
        $links = [
            '/dashboard',
            '/students',
            '/guardians',
            '/teachers',
            '/academic-years',
            '/grade-levels',
            '/subjects',
            '/sections',
            '/rooms',
            '/periods',
            '/enrollments',
            '/timetable',
            '/attendance',
            '/attendance-sessions',
            '/exams',
            '/exam-results',
            '/assignments',
            '/quizzes',
            '/submissions',
            '/report-cards',
            '/materials',
            '/assessments',
            '/calendar',
            '/announcements',
            '/content/news',
            '/content/events',
            '/content/pages',
            '/messages',
            '/documents',
            '/admissions/applications',
            '/admissions/review',
            '/finance/invoices',
            '/finance/fee-structures',
            '/finance/fee-types',
            '/finance/discounts',
            '/finance/payments',
            '/finance/refunds',
            '/reports',
            '/settings/users',
            '/roles',
            '/schools',
            '/audit-logs',
            '/settings/school',
            '/settings/academic',
            '/settings/attendance',
            '/settings/grading',
            '/settings/localization',
            '/settings/translations',
            '/settings/notifications-config',
            '/settings/email',
            '/settings/sms',
            '/settings/payments',
            '/settings/payments/logs',
            '/settings/security',
            '/settings/appearance',
            '/settings/navigation',
            '/settings/profile',
            '/settings/password',
            '/settings/preferences',
            '/settings/security/two-factor',
        ];

        return array_combine($links, array_map(static fn (string $link): array => [$link], $links));
    }

    #[DataProvider('adminLinks')]
    public function test_sidebar_link_loads(string $url): void
    {
        $this->actingAsSuperAdmin(School::factory()->create());

        $this->get($url)->assertOk();
    }

    /**
     * The palettes moved into Settings → Appearance, so the old screen has to
     * forward there rather than 404 on an existing bookmark.
     */
    public function test_the_theme_screen_forwards_to_appearance(): void
    {
        $this->actingAsSuperAdmin(School::factory()->create());

        $this->get('/settings/theme')->assertRedirect(route('settings.appearance'));
    }

    private function actingAsSuperAdmin(School $school): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
