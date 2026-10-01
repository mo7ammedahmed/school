<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Localization\Models\InterfaceTranslation;
use App\Domain\Schools\Models\School;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * The explicit `Gate::policy()` map in `AppServiceProvider` exists because the
 * naming convention silently failed wherever a model and its policy diverged —
 * `TeacherProfile` resolved to no policy at all, and `TeacherController` asked
 * exactly nothing of it. Nothing pinned the map after the fact.
 *
 * This test asks the router which models each action actually binds, resolves
 * every one through the real Gate, and requires each answer to be a policy or a
 * written exception. A model added to a controller signature without a policy
 * fails the build by name, which is how the convention's next silent failure
 * gets caught at the review rather than in production.
 *
 * The second case covers the same ground for the `App\Models\*` aliases: each
 * one is a thin subclass added to the same map, and if one ever falls out of it
 * the alias would answer to a different policy (or none) than the domain model
 * it wraps.
 */
class PolicyDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Route-bound models that deliberately have no policy, and why.
     *
     * @var array<class-string<Model>, string>
     */
    private const BOUND_WITHOUT_POLICY = [
        AdmissionApplication::class => 'The review queue is a shared staff queue, not a per-row decision: '
            .'`permission:manage-admissions` gates the group and the controller repeats it, and every '
            .'holder is meant to see every application in the school.',
        InterfaceTranslation::class => 'The interface-copy admin sits behind `role:super_admin` inside '
            .'the settings group, and a super admin bypasses every policy through `Gate::before`, so a '
            .'policy here would be dead code.',
        School::class => 'A school is the tenant itself, not a row inside one. `manage-schools` is '
            .'super-admin-only, and the running record documents the unscoped `School` binding as '
            .'deliberate (platform provisioning).',
    ];

    /**
     * `App\Models\Classroom` is a compatibility alias for `Room` — it points at
     * the `rooms` table because Eloquent would otherwise derive a `classrooms`
     * table that does not exist — and it carries its own `manage-classrooms`
     * permission, which the seeder derives from `manage-rooms`, so every role
     * that can reach a room can reach the alias. No route binds it today, and
     * consolidating the alias belongs with the model-layer consolidation rather
     * than this phase.
     *
     * @var array<class-string<Model>, string>
     */
    private const ALIASES_WITH_OWN_POLICY = [
        Classroom::class => 'Deliberate compatibility alias with a derived, equivalent permission.',
    ];

    public function test_every_model_a_route_binds_resolves_to_a_policy_or_is_documented(): void
    {
        $without = [];
        $resolved = [];

        foreach ($this->modelsBoundByRoutes() as $model) {
            if (Gate::getPolicyFor($model) === null) {
                $without[] = $model;

                continue;
            }

            $resolved[] = $model;
        }

        $this->assertNotEmpty($resolved, 'No route-bound model resolved to a policy, so this test proved nothing.');

        $this->assertSame(
            $this->sorted(array_keys(self::BOUND_WITHOUT_POLICY)),
            $this->sorted($without),
            'A model bound in a route has no policy and is not in the documented exception list. Add a policy, '
            .'or name the model here with the gate that makes a per-row decision unnecessary.',
        );
    }

    public function test_an_app_model_alias_resolves_to_the_same_policy_as_its_domain_model(): void
    {
        $checked = 0;

        foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
            $alias = 'App\\Models\\'.basename($file, '.php');

            if (! class_exists($alias) || ! is_subclass_of($alias, Model::class)) {
                continue;
            }

            $domain = get_parent_class($alias);

            if ($domain === false || ! str_starts_with($domain, 'App\\Domain\\')) {
                continue;
            }

            if (array_key_exists($alias, self::ALIASES_WITH_OWN_POLICY)) {
                continue;
            }

            $aliasPolicy = Gate::getPolicyFor($alias);
            $domainPolicy = Gate::getPolicyFor($domain);

            $this->assertNotNull(
                $aliasPolicy,
                "{$alias} resolves to no policy while its domain model does; the alias is missing from the map.",
            );

            $this->assertSame(
                $domainPolicy === null ? null : $domainPolicy::class,
                $aliasPolicy::class,
                "{$alias} and {$domain} resolve to different policies, so the same action answers differently "
                .'depending on which class name the controller hinted.',
            );

            $checked++;
        }

        $this->assertGreaterThan(
            10,
            $checked,
            'Almost no aliases were checked, which means the discovery walk itself is broken rather than the map.',
        );
    }

    // ------------------------------------------------------------------

    /**
     * Every model type hinted by a controller action the router can dispatch,
     * read by reflection so nothing here is a second, hand-kept list.
     *
     * @return list<class-string<Model>>
     */
    private function modelsBoundByRoutes(): array
    {
        $models = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uses = $route->getAction('uses');

            if (! is_string($uses)) {
                continue;
            }

            [$class, $method] = str_contains($uses, '@')
                ? explode('@', $uses, 2)
                : [$uses, '__invoke'];

            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                $name = $type->getName();

                if (class_exists($name) && is_subclass_of($name, Model::class)) {
                    $models[$name] = $name;
                }
            }
        }

        return array_values($models);
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
