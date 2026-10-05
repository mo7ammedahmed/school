<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('reports/index', [
            'reports' => [],
        ]);
    }
}
