<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Learning\Models\Quiz;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The role matrix, derived rather than written down.
 *
 * {@see RouteGroupAuthorizationTest} proves two roles are kept out of four
 * screens. It says nothing about the other six roles, nothing about the other
 * forty-odd protected groups, and nothing about the roles that are *supposed* to
 * get in — a suite that only ever asserts 403 passes just as happily against a
 * route that turned every caller away.
 *
 * So this enumerates the `auth + school.context` group from the router at run
 * time, reads each route's own `permission:` / `role:` middleware, and works out
 * what that route should answer for a role whose permissions were read off the
 * seeded role — never from a list typed out here. A hand-written table would be a
 * second copy of {@see RoleSeeder}, and a second copy of a seeder is the thing
 * that rots: it goes stale quietly, and then it lies.
 *
 * A resource's seven actions share one group-level `permission:`, so each
 * distinct gate is sampled once and the set of gates the matrix exercised is
 * asserted equal to the set the group declares: a new gate fails the build
 * instead of going untested. The routes with no gate at all are asserted against
 * an explicit list for the same reason — a new ungated screen is a finding, not
 * a pass.
 *
 * This matrix used to carry an exclusion list of four gates whose routes named
 * controller methods that were never written, so it could not ask them
 * anything. Those routes are retired (security Phase 5,
 * {@see RouteIntegrityTest}) and the list is gone: every gate the group
 * declares is exercised.
 */
class RoleRouteMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The middleware the application group is registered behind. A route
     * carrying it has already been signed-in-checked and school-resolved, so the
     * only question left is whether the caller's role may open it.
     */
    private const GROUP_MIDDLEWARE = 'school.context';

    /**
     * The roles the matrix covers, which is every role RoleSeeder creates.
     *
     * @var list<string>
     */
    private const ROLES = [
        'super_admin',
        'school_admin',
        'principal',
        'registrar',
        'teacher',
        'accountant',
        'student',
        'guardian',
    ];

    /**
     * Every route in the protected group that declares no permission, role or
     * gate, and why it is defensible for it to be open to any signed-in member
     * of the school.
     *
     * This list is the guard. A screen added to the group with no middleware is
     * reachable by a guardian and reported by nobody.
     *
     * @var array<string, string>
     */
    private const EXPECTED_UNGATED_ROUTES = [
        'GET /live/{liveSession}/hls/{file}' => 'Live HLS playback rechecks active membership, '
            .'live status and LiveSessionPolicy view for every playlist and segment. '
            .'Students can read only their active sections; they cannot publish.',
        'GET /materials/{material}/stream' => 'Playback for a lesson video or a finished '
            .'recording. Who may watch is a fact about the row rather than the role: staff hold '
            .'manage-materials, and a student passes only when the material is published, its kind '
            .'is video or recording, and the offering teaches a section they are enrolled in. A '
            .'group-level permission would either lock students out of their own lessons or open '
            .'every document to them, so MaterialPolicy decides and the route answers 404/403 '
            .'without ever handing the file to a caller the policy refused.',
        'GET /notifications' => 'The caller\'s own inbox, read from their own conversations. '
            .'There is no school-wide list here to leak, so a permission would only gate a '
            .'private row the ownership check already covers.',
        'GET /ui/copy/catalog' => 'The interface dictionary. Public copy, and it never calls a '
            .'provider, so serving it to everyone is what keeps Arabic working on a page that '
            .'arrived before its catalog did.',
        'GET /ui/copy/version' => 'A version string for that same catalog, with no content in it.',
        'GET /settings/password' => 'The caller\'s own password form.',
        'GET /settings/preferences' => 'The caller\'s own locale, timezone and display '
            .'preferences — per user, not per school.',
        'GET /settings/profile' => 'The caller\'s own profile form.',
        'GET /settings/security/two-factor' => 'The caller\'s own two-factor form. A shared '
            .'account-wide setting would be the thing to gate, and that is a different screen.',
        'POST /settings/password' => 'The caller changing their own password.',
        'POST /settings/preferences' => 'The save half of the caller\'s own preferences.',
        'POST /settings/profile' => 'The caller editing their own name and contact details.',
        'POST /settings/security/two-factor/disable' => 'The caller turning two-factor off on '
            .'their own account.',
        'POST /settings/security/two-factor/enable' => 'The caller turning two-factor on on their '
            .'own account.',
        'POST /settings/theme/mode' => 'Light and dark mode, which the comment at '
            .'routes/web.php:504 calls out as a personal preference and not an admin setting.',
        'POST /translate' => 'Translates one field the caller is typing. Any author needs it, and '
            .'it is rate limited per user because it spends the school\'s provider credits.',
        'POST /translate/save' => 'The save half of that same field translation.',
        'POST /ui/copy' => 'Translates the dashboard\'s own interface words, for the same reason '
            .'as /translate and under the same per-user rate limit.',
    ];

    private ?School $school = null;

    /** @var array<string, list<string>> */
    private array $permissionsByRole = [];

    /** @var array<string, int>|null */
    private ?array $prerequisiteIds = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        // The registrar keeps a resolved copy of the role and permission tables;
        // drop it so what the middleware sees is what the seeders just wrote.
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function roles(): array
    {
        $cases = [];

        foreach (self::ROLES as $role) {
            $cases[$role] = [$role];
        }

        return $cases;
    }

    /**
     * The matrix itself: one case per role, and inside it every distinct gate.
     */
    #[DataProvider('roles')]
    public function test_a_role_reaches_exactly_the_gates_its_permissions_open(string $roleName): void
    {
        $permissions = $this->permissionsOf($roleName);

        $this->actingAsSeededRole($roleName);

        $exercised = [];

        foreach (self::gatesInProtectedGroup() as $gate) {
            $this->assertTrue(
                Route::has($gate['name']),
                "The route sampled for gate [{$gate['gate']}] is [{$gate['name']}], which is not "
                .'registered, so this case would prove nothing.',
            );

            $allowed = self::expectationFor($gate, $roleName, $permissions);
            $status = $this->requestThe($gate)->getStatusCode();

            if ($allowed) {
                $this->assertNotSame(
                    403,
                    $status,
                    $this->describe($roleName, $gate, $permissions, $status, 'must not be refused'),
                );

                $this->assertLessThan(
                    500,
                    $status,
                    $this->describe(
                        $roleName,
                        $gate,
                        $permissions,
                        $status,
                        'answered with a server error, which is not an authorization result — seed '
                        .'the rows the screen needs, or exclude the gate with a written reason',
                    ),
                );
            } else {
                $this->assertSame(
                    403,
                    $status,
                    $this->describe($roleName, $gate, $permissions, $status, 'must be refused'),
                );
            }

            foreach ($gate['permissions'] as $alternative) {
                $exercised = [...$exercised, ...$alternative];
            }
        }

        $this->assertSame(
            self::permissionStringsDeclaredByProtectedGroup(),
            self::sorted(array_unique($exercised)),
            'The matrix did not exercise every permission string the protected group declares. A '
            .'gate was added, or the sampling missed one; either way a screen is untested.',
        );
    }

    /**
     * A new screen with no gate on it is a finding, not a pass.
     */
    public function test_the_ungated_routes_in_the_protected_group_are_the_documented_ones(): void
    {
        $this->assertSame(
            self::sorted(array_keys(self::EXPECTED_UNGATED_ROUTES)),
            self::ungatedRoutesInProtectedGroup(),
            'A route was added to the protected group carrying no permission, role or gate. If '
            .'that is deliberate, say why in EXPECTED_UNGATED_ROUTES. If it is not, it is open to '
            .'every signed-in member of the school.',
        );
    }

    /**
     * A ninth role would be a ninth untested actor.
     */
    public function test_the_matrix_covers_every_seeded_role(): void
    {
        $this->assertSame(
            self::sorted(self::ROLES),
            self::sorted(Role::pluck('name')->all()),
            'RoleSeeder creates a role this matrix does not cover, so nobody has tested what that '
            .'role can reach.',
        );
    }

    // ------------------------------------------------------------------
    // What a gate means
    // ------------------------------------------------------------------

    /**
     * Middleware on a route are ANDed; the `|` inside one of them is an OR. That
     * is exactly what the alias resolves to, so the expectation is read off the
     * route rather than restated: a role is entitled when, for every `permission:`
     * entry, it holds at least one of the names that entry offers — and likewise
     * for every `role:` entry.
     *
     * @param  list<string>  $permissions
     */
    private static function expectationFor(array $gate, string $roleName, array $permissions): bool
    {
        $groups = [...$gate['permissions'], ...$gate['roles']];

        foreach ($groups as $group) {
            $satisfied = false;

            foreach ($group as $name) {
                $satisfied = $name === $roleName || in_array($name, $permissions, true);

                if ($satisfied) {
                    break;
                }
            }

            if (! $satisfied) {
                return false;
            }
        }

        return $groups !== [];
    }

    private function describe(
        string $roleName,
        array $gate,
        array $permissions,
        int $status,
        string $requirement,
    ): string {
        $held = array_intersect($gate['permissions'][0] ?? [], $permissions);

        return sprintf(
            'The %s role asked %s %s, gated by [%s], holding [%s] of what it asks. It answered '
            .'%d. The %s role %s.',
            $roleName,
            $gate['method'],
            '/'.$gate['uri'].($gate['name'] === '' ? '' : " ({$gate['name']})"),
            $gate['gate'],
            $held === [] ? 'none' : implode(', ', $held),
            $status,
            $roleName,
            $requirement,
        );
    }

    // ------------------------------------------------------------------
    // Enumerating the group from the router
    // ------------------------------------------------------------------

    /**
     * Every distinct gate in the protected group, with the one route that stands
     * for it and how many routes share it.
     *
     * @return array<string, array{name: string, method: string, uri: string, parameters: list<string>, gate: string, permissions: list<list<string>>, roles: list<list<string>>, routes: int}>
     */
    private static function gatesInProtectedGroup(): array
    {
        $byGate = [];

        foreach (self::protectedRoutes() as $route) {
            $key = implode(',', $route['gate']);

            if ($key === '') {
                continue;
            }

            $seen = $byGate[$key]['routes'] ?? 0;
            $incumbent = $byGate[$key] ?? null;

            if ($incumbent === null || self::sampleRank($route) < self::sampleRank($incumbent)) {
                $byGate[$key] = [
                    'name' => $route['name'],
                    'method' => $route['method'],
                    'uri' => $route['uri'],
                    'parameters' => $route['parameters'],
                    'gate' => $key,
                    'permissions' => self::alternativesIn($route['gate'], 'permission'),
                    'roles' => self::alternativesIn($route['gate'], 'role'),
                    'routes' => $seen + 1,
                ];
            } else {
                $byGate[$key]['routes'] = $seen + 1;
            }
        }

        ksort($byGate);

        return $byGate;
    }

    /**
     * One entry per `alias:` middleware, holding the alternatives that entry
     * offers — `manage-a|manage-b` is one entry with two alternatives, and two
     * separate `permission:` entries are two entries the role must both satisfy.
     *
     * @param  list<string>  $gate
     * @return list<list<string>>
     */
    private static function alternativesIn(array $gate, string $alias): array
    {
        $groups = [];

        foreach ($gate as $entry) {
            if (! str_starts_with($entry, $alias.':')) {
                continue;
            }

            [, $names] = explode(':', $entry, 2);
            $groups[] = explode('|', $names);
        }

        return $groups;
    }

    /**
     * How little the harness has to invent to make this route requestable: a
     * named route over an unnamed one, a resource index over a side screen, a GET
     * over a write, a route with no URI parameters over one that needs a row,
     * then the plainest URI left.
     *
     * @param  array{name: string, method: string, uri: string, parameters: list<string>}  $route
     * @return list<int|string>
     */
    private static function sampleRank(array $route): array
    {
        return [
            $route['name'] === '' ? 1 : 0,
            str_ends_with($route['name'], '.index') ? 0 : 1,
            $route['method'] === 'GET' ? 0 : 1,
            count($route['parameters']),
            count(explode('/', $route['uri'])),
            strlen($route['uri']),
            $route['name'],
        ];
    }

    /**
     * Every route behind `auth + school.context`, read from the router.
     *
     * Nothing is shelled out and nothing is hardcoded: `php artisan route:list`
     * inside a test would make the suite depend on a process it cannot assert on.
     *
     * @return list<array{name: string, method: string, uri: string, parameters: list<string>, gate: list<string>}>
     */
    private static function protectedRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $middleware = $route->middleware();

            if (! in_array(self::GROUP_MIDDLEWARE, $middleware, true)) {
                continue;
            }

            $gate = [];

            foreach ($middleware as $entry) {
                if (preg_match('/^(permission|role):(.+)$/', $entry) === 1) {
                    $gate[] = $entry;

                    continue;
                }

                if (str_starts_with($entry, 'can:')) {
                    throw new LogicException(sprintf(
                        'Route [%s] in the protected group is gated by [%s], which this matrix '
                        .'cannot derive an expectation from: a policy decides who may pass, and '
                        .'only the policy knows. Cover that one by hand.',
                        $route->getName() ?? $route->uri(),
                        $entry,
                    ));
                }
            }

            sort($gate);

            $routes[] = [
                'name' => (string) $route->getName(),
                'method' => self::requestMethodFor($route->methods()),
                'uri' => $route->uri(),
                'parameters' => self::uriParametersOf($route->uri()),
                'gate' => $gate,
            ];
        }

        return $routes;
    }

    /**
     * @param  list<string>  $methods
     */
    private static function requestMethodFor(array $methods): string
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            if (in_array($method, $methods, true)) {
                return $method;
            }
        }

        return 'GET';
    }

    /**
     * @return list<string>
     */
    private static function uriParametersOf(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        return $matches[1];
    }

    /**
     * Every distinct permission string the protected group names.
     *
     * @return list<string>
     */
    private static function permissionStringsDeclaredByProtectedGroup(): array
    {
        $strings = [];

        foreach (self::gatesInProtectedGroup() as $gate) {
            foreach ($gate['permissions'] as $alternative) {
                $strings = [...$strings, ...$alternative];
            }
        }

        return self::sorted(array_unique($strings));
    }

    /**
     * @return list<string>
     */
    private static function ungatedRoutesInProtectedGroup(): array
    {
        $ungated = [];

        foreach (self::protectedRoutes() as $route) {
            if ($route['gate'] === []) {
                $ungated[] = $route['method'].' /'.$route['uri'];
            }
        }

        return self::sorted(array_unique($ungated));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    // ------------------------------------------------------------------
    // Acting
    // ------------------------------------------------------------------

    /**
     * Signs a user in as one of the *seeded* roles, inside a real school.
     *
     * The roles come from RoleSeeder rather than being built here, because the
     * point is to test what the seeder produced. The user has an active
     * membership in the school the request resolves, which is what makes this a
     * privilege test and not a tenant test: nothing about the membership is
     * wrong. Only the role is.
     */
    private function actingAsSeededRole(string $roleName): User
    {
        $this->school = School::factory()->create();
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $user->assignRole(Role::findByName($roleName, 'web'));

        $this->actingAs($user);
        $this->app['session']->put('school_id', $this->school->id);

        return $user;
    }

    /**
     * @return list<string>
     */
    private function permissionsOf(string $roleName): array
    {
        if (! isset($this->permissionsByRole[$roleName])) {
            $this->permissionsByRole[$roleName] = self::sorted(
                Role::findByName($roleName, 'web')->permissions()->pluck('name')->all(),
            );
        }

        return $this->permissionsByRole[$roleName];
    }

    private function requestThe(array $gate): TestResponse
    {
        $uri = '/'.ltrim($gate['uri'], '/');

        foreach ($gate['parameters'] as $parameter) {
            $uri = str_replace(
                ['{'.$parameter.'?}', '{'.$parameter.'}'],
                (string) $this->idFor($parameter),
                $uri,
            );
        }

        return match ($gate['method']) {
            'POST' => $this->post($uri),
            'PUT' => $this->put($uri),
            'PATCH' => $this->patch($uri),
            'DELETE' => $this->delete($uri),
            default => $this->get($uri),
        };
    }

    /**
     * The rows the sampled screens cannot render without.
     *
     * Only one sampled route carries a URI parameter and it is behind a quiz, so
     * the whole chain is built once per test and shared.
     */
    private function idFor(string $parameter): int
    {
        $this->prerequisiteIds ??= $this->buildPrerequisites();

        return $this->prerequisiteIds[$parameter] ?? throw new LogicException(sprintf(
            'A sampled route in the protected group has a [%s] parameter and this matrix has no '
            .'row to put in it. Either that is a poor sample for its gate, or the harness needs a '
            .'prerequisite — without one the request would 404 and assert nothing at all.',
            $parameter,
        ));
    }

    /**
     * @return array<string, int>
     */
    private function buildPrerequisites(): array
    {
        $school = $this->school;

        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id]);
        $teacher = TeacherProfile::factory()->create(['school_id' => $school->id]);

        $offering = Offering::create([
            'school_id' => $school->id,
            'academic_year_id' => $section->academic_year_id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'name' => 'Matrix offering',
        ]);

        $quiz = Quiz::create([
            'school_id' => $school->id,
            'offering_id' => $offering->id,
            'title' => 'Matrix quiz',
            'is_published' => true,
        ]);

        $student = Student::factory()->create(['school_id' => $school->id, 'user_id' => auth()->id()]);
        $section->students()->attach($student->id, [
            'school_id' => $school->id,
            'academic_year_id' => $section->academic_year_id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return [
            'subject' => $subject->id,
            'section' => $section->id,
            'teacher' => $teacher->id,
            'quiz' => $quiz->id,
        ];
    }
}
