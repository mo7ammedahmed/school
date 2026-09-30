<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Schools\Models\School;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The catalogue and the policies are written by different hands and checked by
 * different tests, so nothing stopped a policy from asking for a permission
 * nobody had ever seeded.
 *
 * `hasPermissionTo('some-name')` does not return false for a name that does not
 * exist: it resolves the name through `Permission::findByName()`, which throws
 * `PermissionDoesNotExist`. So the failure was not a quiet deny — it was a 500
 * on whichever request happened to reach the policy. Thirteen of these were
 * reachable, through POST /translate/save, which authorises whatever bilingual
 * model the request names.
 *
 * These two cases are the guard. The first pins the twenty-two names this
 * commit added; the second reads the policy sources and pins the *whole*
 * checked surface, so the twenty-third name fails the build rather than a
 * user's browser.
 */
class PermissionNamesExistTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Names checked by a policy that the catalogue did not carry.
     *
     * @return array<string, array{string}>
     */
    public static function derivedPermissionNames(): array
    {
        $names = [
            'manage-grading-scales',
            'manage-grading-categories',
            'manage-assessment-scores',
            'manage-exam-results',
            'manage-attendance-records',
            'manage-attendance-sessions',
            'manage-conversations',
            'manage-notifications',
            'manage-news',
            'manage-events',
            'manage-faqs',
            'manage-staff-profiles',
            'manage-contact-leads',
            'manage-document-categories',
            'manage-gateway-transactions',
            'manage-webhook-events',
            'manage-invoice-lines',
            'manage-payment-allocations',
            'manage-memberships',
            'manage-quiz-attempts',
            'manage-school-settings',
            'manage-classrooms',
        ];

        return array_combine($names, array_map(fn (string $n): array => [$n], $names));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    /**
     * The point of the first case: not that the name is in the table, but that
     * the call a policy makes cannot throw.
     */
    #[DataProvider('derivedPermissionNames')]
    public function test_a_policy_can_resolve_the_permission_it_asks_for(string $name): void
    {
        $school = School::factory()->create();
        $user = $this->actingAsSchoolUser($school);

        $this->assertSame(
            $name,
            Permission::findByName($name)->name,
            "The permission [{$name}] is not seeded, so Permission::findByName() throws for it.",
        );

        // The actual call a policy makes. This is what used to raise
        // PermissionDoesNotExist and turn into a 500.
        $this->assertFalse(
            $user->hasPermissionTo($name),
            "A user with no roles must not hold [{$name}] — the grant, not the resolution, is the claim.",
        );
    }

    public function test_every_permission_name_a_policy_checks_is_seeded(): void
    {
        $missing = [];

        foreach ($this->permissionNamesCheckedByPolicies() as $file => $names) {
            foreach ($names as $name) {
                if (! Permission::where('name', $name)->exists()) {
                    $missing[] = "{$file} checks [{$name}]";
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These policy permission names are not seeded, so hasPermissionTo() throws '
            .'PermissionDoesNotExist on the first request that reaches them: '.implode('; ', $missing),
        );
    }

    /**
     * Every `hasPermissionTo('x')` / `checkPermissionTo('x')` literal in the
     * policy sources, keyed by file for a failure message that names the file.
     *
     * The scan is over source text rather than over the policy classes because
     * calling a policy method needs a model, a user and a session, and a
     * reflection walk would still miss a name in a branch a fixture cannot
     * reach. A literal is all that matters here: a name built at runtime is a
     * name some other test already has to cover.
     *
     * @return array<string, list<string>>
     */
    private function permissionNamesCheckedByPolicies(): array
    {
        $paths = array_merge(
            glob(app_path('Domain/*/Policies/*.php')) ?: [],
            glob(app_path('Policies/*.php')) ?: [],
        );

        $found = [];

        foreach ($paths as $path) {
            $names = [];

            preg_match_all(
                "/(?:hasPermissionTo|checkPermissionTo)\(\s*'([a-z0-9\-]+)'\s*\)/",
                (string) file_get_contents($path),
                $matches,
            );

            foreach (array_unique($matches[1]) as $name) {
                $names[] = $name;
            }

            if ($names !== []) {
                $found[str_replace(base_path().DIRECTORY_SEPARATOR, '', $path)] = $names;
            }
        }

        return $found;
    }
}
