<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function index()
    {
        return response('OK', 200);
    }
}
