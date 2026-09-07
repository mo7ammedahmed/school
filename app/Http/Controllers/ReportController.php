<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $reports = [
            ['id' => 1, 'name' => 'Student Enrollment Report', 'type' => 'students', 'generated_at' => now()->toDateString()],
            ['id' => 2, 'name' => 'Attendance Summary', 'type' => 'attendance', 'generated_at' => now()->toDateString()],
            ['id' => 3, 'name' => 'Financial Report', 'type' => 'finance', 'generated_at' => now()->toDateString()],
        ];

        return Inertia::render('reports/index', [
            'reports' => $reports,
        ]);
    }
}
