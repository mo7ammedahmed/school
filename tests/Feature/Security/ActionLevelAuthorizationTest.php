<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Models\Guardian;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The action-level check, exercised directly.
 *
 * The route group is the outer boundary: it asks "may this role open this
 * screen". {@see RoleRouteMatrixTest} proves that boundary holds. What it cannot
 * see is what happens *inside* an action once the caller is through — and that is
 * where a record-level decision belongs, because two people holding the same
 * permission can be entitled to different rows.
 *
 * Each user here holds the real permission its policy asks for, so the route
 * middleware is a non-factor and a refusal can only have come from the action.
 * What they lack is a claim on the row in front of them.
 *
 * Three cases per action, because a check that denies everything would satisfy
 * the first one alone:
 *
 *  - a row from another school is refused;
 *  - a row from the caller's own school is allowed through to validation;
 *  - a row from the caller's own school is still refused when the session points
 *    somewhere else, which no per-row check can catch.
 */
class ActionLevelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    /**
     * The real permission strings, one per binding, read off the policies rather
     * than invented — a test that granted a permission no policy asks for would
     * pass the refusal cases for the wrong reason.
     *
     * `user` carries two because `/settings/*` is gated on
     * `manage-settings|manage-schools` at the group and `manage-users` on the
     * resource. Middleware on a route is ANDed, so both are required to reach
     * the action at all. Omitting the group permission made the middleware
     * refuse every user case with a 403, which the refusal test would have
     * accepted as a pass while the policy was never consulted.
     *
     * @var array<string, list<string>>
     */
    private const PERMISSION = [
        'student' => ['manage-students'],
        'guardian' => ['manage-guardians'],
        'teacher' => ['manage-teachers'],
        'room' => ['manage-rooms'],
        'user' => ['manage-users', 'manage-settings'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->otherSchool = School::factory()->create();

        foreach (self::PERMISSION as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }
    }

    /**
     * The show/edit/mutating actions that now consult a policy.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function guardedActions(): array
    {
        return [
            'student show' => ['GET', 'students.show', 'students/{student}', 'student'],
            'student edit' => ['GET', 'students.edit', 'students/{student}/edit', 'student'],
            'student update' => ['PUT', 'students.update', 'students/{student}', 'student'],
            'student destroy' => ['DELETE', 'students.destroy', 'students/{student}', 'student'],
            'guardian show' => ['GET', 'guardians.show', 'guardians/{guardian}', 'guardian'],
            'guardian edit' => ['GET', 'guardians.edit', 'guardians/{guardian}/edit', 'guardian'],
            'guardian update' => ['PUT', 'guardians.update', 'guardians/{guardian}', 'guardian'],
            'guardian destroy' => ['DELETE', 'guardians.destroy', 'guardians/{guardian}', 'guardian'],
            'teacher show' => ['GET', 'teachers.show', 'teachers/{teacher}', 'teacher'],
            'teacher edit' => ['GET', 'teachers.edit', 'teachers/{teacher}/edit', 'teacher'],
            'teacher update' => ['PUT', 'teachers.update', 'teachers/{teacher}', 'teacher'],
            'room show' => ['GET', 'rooms.show', 'rooms/{room}', 'room'],
            'room edit' => ['GET', 'rooms.edit', 'rooms/{room}/edit', 'room'],
            'room update' => ['PUT', 'rooms.update', 'rooms/{room}', 'room'],
            'room destroy' => ['DELETE', 'rooms.destroy', 'rooms/{room}', 'room'],
        ];
    }

    /**
     * `User` is the case where the action's own policy is the *only* defence.
     *
     * A user row has no `school_id`, so `BelongsToSchool` cannot narrow the
     * binding and no upstream layer refuses a stranger's id: the route resolves
     * it and the action runs. When `UserController` stopped hand-rolling its
     * `ensureUserBelongsToCurrentSchool()` abort in favour of `UserPolicy`, this
     * is the check that took over, so it is held to an exact 403 rather than the
     * 403-or-404 the bound models above accept.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function userActions(): array
    {
        return [
            'user show' => ['GET', 'settings.users.show', 'settings/users/{user}'],
            'user edit' => ['GET', 'settings.users.edit', 'settings/users/{user}/edit'],
            'user update' => ['PUT', 'settings.users.update', 'settings/users/{user}'],
            'user destroy' => ['DELETE', 'settings.users.destroy', 'settings/users/{user}'],
        ];
    }

    #[DataProvider('userActions')]
    public function test_a_user_action_refuses_a_stranger_with_403(
        string $method,
        string $routeName,
        string $uri,
    ): void {
        $this->assertRouteExists($routeName);

        // An active member of *another* school, so the only thing separating
        // them from this screen is the policy.
        $stranger = $this->memberOf($this->otherSchool);

        $this->actingAsHolderOf('user', $this->school);

        $this->callAction($method, str_replace('{user}', (string) $stranger->id, $uri))
            ->assertForbidden();
    }

    #[DataProvider('userActions')]
    public function test_a_user_action_allows_a_colleague_with_403(string $method, string $routeName, string $uri): void
    {
        $this->assertRouteExists($routeName);

        $colleague = $this->memberOf($this->school);

        $this->actingAsHolderOf('user', $this->school);

        $response = $this->callAction($method, str_replace('{user}', (string) $colleague->id, $uri));

        $this->assertNotSame(403, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }

    #[DataProvider('guardedActions')]
    public function test_an_action_refuses_a_record_from_another_school(
        string $method,
        string $routeName,
        string $uri,
        string $binding,
    ): void {
        $this->assertRouteExists($routeName);

        $record = $this->recordFor($binding, $this->otherSchool);

        $this->actingAsHolderOf($binding, $this->school);

        $this->assertRefused(
            $this->callAction($method, $this->uriFor($uri, $binding, $record)),
            $routeName,
        );
    }

    #[DataProvider('guardedActions')]
    public function test_an_action_allows_a_record_from_its_own_school(
        string $method,
        string $routeName,
        string $uri,
        string $binding,
    ): void {
        $this->assertRouteExists($routeName);

        $record = $this->recordFor($binding, $this->school);

        $this->actingAsHolderOf($binding, $this->school);

        $response = $this->callAction($method, $this->uriFor($uri, $binding, $record));

        $this->assertNotSame(403, $response->getStatusCode(), $this->describe($response, 'refused'));
        $this->assertLessThan(
            500,
            $response->getStatusCode(),
            $this->describe($response, 'answered with a server error rather than an authorization result'),
        );
    }

    #[DataProvider('guardedActions')]
    public function test_an_action_refuses_when_the_session_points_elsewhere(
        string $method,
        string $routeName,
        string $uri,
        string $binding,
    ): void {
        $this->assertRouteExists($routeName);

        // The record is the caller's own school's, but they are working in
        // another one. Only the action's own check can catch this, because
        // nothing about the row is wrong.
        $record = $this->recordFor($binding, $this->school);

        $this->actingAsHolderOf($binding, $this->otherSchool);

        $this->assertRefused(
            $this->callAction($method, $this->uriFor($uri, $binding, $record)),
            $routeName,
        );
    }

    /**
     * A foreign row must be refused, but *how* depends on which layer catches
     * it, and both answers are correct:
     *
     *  - `BelongsToSchool` narrows route-model binding, so for a tenant-bound
     *    model the id never resolves and the answer is 404. That is the better
     *    answer of the two — a 403 confirms the row exists elsewhere.
     *  - `User` carries no `school_id` and is not tenant-bound, so nothing
     *    upstream can refuse it and the policy inside the action is the only
     *    thing standing there. That is the 403.
     *
     * Asserting one fixed code would pin the test to whichever layer happens to
     * run first, and would call a working defence a regression the next time a
     * model gains or loses `BelongsToSchool`.
     */
    private function assertRefused(TestResponse $response, string $routeName): void
    {
        $status = $response->getStatusCode();

        $this->assertContains(
            $status,
            [403, 404],
            sprintf(
                'Route [%s] answered %d for a record belonging to another school. The request was '
                .'neither refused nor treated as missing, so the row was readable by a school that '
                .'does not own it.',
                $routeName,
                $status,
            ),
        );
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    private function uriFor(string $uri, string $binding, Student|Guardian|TeacherProfile|Room $record): string
    {
        return str_replace('{'.$binding.'}', (string) $record->id, $uri);
    }

    private function recordFor(string $binding, School $school): Student|Guardian|TeacherProfile|Room
    {
        $record = match ($binding) {
            'student' => Student::withoutSchoolScope()->create([
                'school_id' => $school->id,
                'first_name' => 'Row',
                'last_name' => 'Holder',
            ]),
            'guardian' => Guardian::withoutSchoolScope()->create([
                'school_id' => $school->id,
                'first_name' => 'Row',
                'last_name' => 'Holder',
                'email' => 'parent-'.$school->id.'@example.com',
                'phone' => '0500000000',
                'relationship' => 'father',
            ]),
            'teacher' => TeacherProfile::withoutSchoolScope()->create([
                'school_id' => $school->id,
                'first_name' => 'Row',
                'last_name' => 'Holder',
            ]),
            'room' => Room::withoutSchoolScope()->create([
                'school_id' => $school->id,
                'name_en' => 'Room '.$school->id,
                'code' => 'R-'.$school->id,
                'room_type' => 'classroom',
                'capacity' => 20,
            ]),
            default => throw new \LogicException(sprintf(
                'No fixture row is defined for the binding [%s]. Every binding in guardedActions() and '
                .'userActions() needs one, or the case silently asserts nothing.',
                $binding,
            )),
        };

        return $record;
    }

    /**
     * A member of `$school` holding the real permission its policy consults.
     *
     * Granted directly rather than through a seeded role, so the test states the
     * single thing it depends on instead of inheriting a role's other rights.
     */
    /**
     * An active member of `$school`, holding the permission its screen needs.
     */
    private function memberOf(School $school): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        return $user;
    }

    private function actingAsHolderOf(string $binding, School $school): User
    {
        $user = $this->memberOf($school);

        // Granted directly rather than through a seeded role, so the test states
        // the single thing it depends on instead of inheriting a role's other
        // rights.
        $user->givePermissionTo(self::PERMISSION[$binding]);
        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }

    private function callAction(string $method, string $uri): TestResponse
    {
        return match ($method) {
            'PUT' => $this->put($uri, [
                'first_name' => 'Renamed',
                'last_name' => 'Row',
                'name' => 'Renamed Row',
                'email' => 'renamed-'.uniqid().'@example.com',
                'relationship' => 'father',
                'phone' => '0500000000',
            ]),
            'DELETE' => $this->delete($uri),
            default => $this->get($uri),
        };
    }

    private function assertRouteExists(string $routeName): void
    {
        $this->assertTrue(
            Route::has($routeName),
            "Route [{$routeName}] is not registered, so this case would prove nothing.",
        );
    }

    private function describe(TestResponse $response, string $requirement): string
    {
        return sprintf(
            'The action answered %d. It %s. A policy that refuses everything would pass the refusal '
            .'cases alone, so the allow case asserts this too.',
            $response->getStatusCode(),
            $requirement,
        );
    }
}
