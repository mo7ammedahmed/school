<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationPermissionContractTest extends TestCase
{
    public function test_every_sidebar_gate_matches_its_get_route(): void
    {
        $source = file_get_contents(resource_path('js/layouts/app-shell.tsx'));
        preg_match_all("/\{ key: '[^']+', href: '([^']+)', icon: \w+, (permissions|roles): (.*?) \}/", $source, $links, PREG_SET_ORDER);
        $this->assertGreaterThan(50, count($links));

        foreach ($links as $link) {
            $route = Route::getRoutes()->match(Request::create($link[1], 'GET'));
            $this->assertContains('school.context', $route->middleware(), $link[1]);
            $expected = [];
            foreach ($route->middleware() as $middleware) {
                $prefix = $link[2] === 'roles' ? 'role:' : 'permission:';
                if (str_starts_with($middleware, $prefix)) {
                    $group = explode('|', substr($middleware, strlen($prefix)));
                    sort($group);
                    $expected[] = $group;
                }
            }
            $declared = json_decode(str_replace("'", '"', $link[3]), true, flags: JSON_THROW_ON_ERROR);
            if ($link[2] === 'roles') {
                $declared = [$declared];
            }
            foreach ($declared as &$group) {
                sort($group);
            }
            unset($group);
            sort($declared);
            sort($expected);
            $this->assertSame($expected, $declared, 'Sidebar gate differs from route: '.$link[1]);
        }
    }
}
