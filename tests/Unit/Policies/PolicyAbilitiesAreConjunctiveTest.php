<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A policy ability must be the *conjunction* of the permission and the tenant,
 * never their disjunction.
 *
 * `return $user->hasPermissionTo('manage-students') || $model->school_id ===
 * session('school_id');` reads as though it grants access to a registrar, and it
 * does that — but it also grants it to a pupil whose only claim is that the row
 * belongs to their own school. `StudentPolicy` was corrected to `&&` in Phase 1
 * and the same shape was left in roughly forty other policies, where it was
 * harmless only by accident: no controller consulted them, so the second operand
 * was never reached on a real request.
 *
 * That accident is what this phase removes. Adding `authorize()` to show/edit and
 * the mutating actions is the whole point, and it is also what would make those
 * forty latent over-grants reachable. The logic has to be right first.
 *
 * `view` is the only ability that may legitimately fall back to ownership rather
 * than a permission — a guardian opening their own child's record is the portal's
 * whole purpose. No policy here expresses that ownership: the fallback is a bare
 * "same school", which every member of the school satisfies, including the pupil
 * the check exists to stop. So ownership is treated as unimplemented and every
 * ability is held to the conjunctive shape. When a real ownership branch is
 * written for a portal, the ability belongs in the exempt list below with a
 * reason, not smuggled back in as a session comparison.
 */
class PolicyAbilitiesAreConjunctiveTest extends TestCase
{
    /**
     * Abilities that deliberately do not follow the conjunctive shape, keyed
     * `path/to/Policy.php::ability`, each mapped to the ownership branch that
     * justifies it.
     *
     * Empty today: no policy expresses a real ownership branch, so nothing is
     * exempt yet. A method rather than a constant because static analysis
     * constant-folds an empty array literal to `array{}`, and then rejects every
     * lookup against it as unreachable — which would make the exemption
     * mechanism itself unaddable.
     *
     * @return array<string, string>
     */
    private static function exempt(): array
    {
        return [];
    }

    /**
     * The abilities that are held to the shape. `viewAny` and `create` take no
     * model and so have no tenant to compare against; the permission is the whole
     * check there.
     *
     * @var list<string>
     */
    private const MODEL_ABILITIES = ['view', 'update', 'delete'];

    /**
     * Every policy on disk, with its abilities and their source.
     *
     * @return array<string, array{file: string, method: string, body: string}>
     */
    public static function abilities(): array
    {
        $cases = [];

        foreach (self::policyFiles() as $file) {
            $class = self::classFor($file);
            $source = (string) file_get_contents($file);
            $reflection = new \ReflectionClass($class);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! in_array($method->getName(), self::MODEL_ABILITIES, true)) {
                    continue;
                }

                $key = $file.'::'.$method->getName();

                $cases[$key] = [
                    'file' => $file,
                    'method' => $method->getName(),
                    'body' => self::bodyOf($source, $method->getName()),
                ];
            }
        }

        return $cases;
    }

    #[DataProvider('abilities')]
    public function test_an_ability_requires_the_permission_and_the_tenant(string $file, string $method, string $body): void
    {
        $key = $file.'::'.$method;
        $exempt = self::exempt();
        $reason = $exempt[$key] ?? null;

        if ($reason !== null) {
            $this->assertNotSame('', trim($reason), "{$key} is exempt from the conjunctive rule without a reason");

            return;
        }

        // Strip comments so prose that happens to contain "||" is not read as
        // code, then collapse whitespace so a body split over several lines is
        // one expression to reason about.
        $expression = (string) preg_replace(['#//[^\n]*#', '#/\*.*?\*/#s', '#\s+#'], ' ', $body);

        $this->assertMatchesRegularExpression(
            '/\)\s*&&|&&\s*\(/',
            $expression,
            sprintf(
                "%s::%s does not conjoin its permission with its tenant check.\n  %s\n"
                .'A disjunction here means any member of the school passes on the tenant alone, which is '
                .'the over-grant this test exists to fail on. Write `permission && tenant`, or list the '
                .'ability in EXEMPT with the ownership branch that justifies it.',
                $file,
                $method,
                $body,
            ),
        );
    }

    /**
     * A guard on the guard: an ability that never mentions a permission at all is
     * a different defect — a check that answers on the tenant alone, with no
     * role behind it.
     */
    #[DataProvider('abilities')]
    public function test_an_ability_checks_a_permission(string $file, string $method, string $body): void
    {
        $this->assertMatchesRegularExpression(
            '/(has|check)PermissionTo\(/',
            $body,
            sprintf(
                '%s::%s consults no permission. A tenant match is not a role: every signed-in member of '
                .'the school satisfies it, so the ability answers yes to a pupil.',
                $file,
                $method,
            ),
        );
    }

    /**
     * @return list<string>
     */
    private static function policyFiles(): array
    {
        // A data provider runs before the container is booted, so `app_path()`
        // and `$this` are both unavailable here; the suite root is two levels up
        // from tests/Unit/Policies.
        $app = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'app';

        $files = [
            ...glob($app.DIRECTORY_SEPARATOR.'Policies'.DIRECTORY_SEPARATOR.'*Policy.php') ?: [],
            ...glob($app.DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'Policies'.DIRECTORY_SEPARATOR.'*Policy.php') ?: [],
        ];

        $files = array_values(array_filter($files, 'is_file'));
        $files = array_values(array_unique($files));
        sort($files);

        if (count($files) < 30) {
            throw new \LogicException(sprintf(
                'Only %d policies were discovered under %s, so this class would pass by testing '
                .'nothing. The glob patterns are pointed at the wrong directory.',
                count($files),
                $app,
            ));
        }

        return $files;
    }

    private static function classFor(string $file): string
    {
        $source = (string) file_get_contents($file);

        preg_match('/^namespace\s+([^;]+);/m', $source, $namespace);
        preg_match('/^(?:final\s+)?class\s+(\w+)/m', $source, $class);

        return ($namespace[1] ?? '').'\\'.($class[1] ?? '');
    }

    /**
     * The source of one ability, braces balanced, so a `||` in a neighbouring
     * method is not attributed to this one.
     */
    private static function bodyOf(string $source, string $method): string
    {
        $start = strpos($source, 'function '.$method.'(');

        if ($start === false) {
            return '';
        }

        $open = strpos($source, '{', $start);
        $depth = 0;
        $length = strlen($source);

        for ($i = (int) $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, (int) $open, $i - (int) $open + 1);
                }
            }
        }

        return '';
    }
}
