<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The route group behind `auth + school.context` asks two questions of every
 * request: is the caller signed in, and does the caller belong to the school in
 * the session. It never asked a third one — may this role open this screen at
 * all.
 *
 * So a guardian or a student of the school's *own* school walks straight into
 * the registrar's student list, the accountant's payment queue, the refund
 * ledger and the audit trail. The ownership check that does exist compares
 * `school_id`, which those users satisfy by definition; it was never a
 * permission check.
 *
 * Each case here is a real screen behind a real named route, and each one is
 * expected to answer 403.
 */
class RouteGroupAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Screens a parent or a pupil has no business opening, whatever school
     * they belong to.
     *
     * @return array<string, array{string, string}>
     */
    public static function adminScreens(): array
    {
        return [
            'student list' => ['/students', 'students.index'],
            'payment queue' => ['/finance/payments', 'finance.payments.index'],
            'refund ledger' => ['/finance/refunds', 'finance.refunds.index'],
            'audit trail' => ['/audit-logs', 'audit-logs.index'],
        ];
    }

    #[DataProvider('adminScreens')]
    public function test_a_guardian_cannot_open_an_admin_screen(string $uri, string $routeName): void
    {
        $this->assertRouteExists($routeName);

        $this->actingAsRole('guardian');

        $this->get($uri)->assertForbidden();
    }

    #[DataProvider('adminScreens')]
    public function test_a_student_cannot_open_an_admin_screen(string $uri, string $routeName): void
    {
        $this->assertRouteExists($routeName);

        $this->actingAsRole('student');

        $this->get($uri)->assertForbidden();
    }

    /**
     * Signs a user in as one of the seeded roles, inside a real school.
     *
     * The user is given an active membership in the same school the request
     * will resolve, which is what makes this a *privilege* test rather than a
     * tenant test: nothing about the membership is wrong here. Only the role is.
     */
    private function actingAsRole(string $roleName): User
    {
        $school = School::factory()->create();
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }

    private function assertRouteExists(string $routeName): void
    {
        $this->assertTrue(
            Route::has($routeName),
            "The route under test [{$routeName}] does not exist, so this case would prove nothing.",
        );
    }
}
