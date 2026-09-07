<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::all();
        return inertia('settings/roles/index', ['roles' => $roles]);
    }
}
