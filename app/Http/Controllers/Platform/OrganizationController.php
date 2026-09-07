<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

class OrganizationController extends Controller
{
    public function index()
    {
        return response('Platform organizations index', 200);
    }

    public function show(string $id)
    {
        return response("Platform organization show: {$id}", 200);
    }
}
