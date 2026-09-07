<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

class SchoolController extends Controller
{
    public function index()
    {
        return response('Platform schools index', 200);
    }

    public function show(string $id)
    {
        return response("Platform school show: {$id}", 200);
    }
}
