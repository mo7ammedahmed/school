<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

class SupportAccessController extends Controller
{
    public function index()
    {
        return response('Platform support access', 200);
    }
}
