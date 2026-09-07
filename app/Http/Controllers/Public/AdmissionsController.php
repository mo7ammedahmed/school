<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Admissions\Models\AdmissionPeriod;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AdmissionsController
{
    public function index(): Response
    {
        $periods = AdmissionPeriod::where('is_active', true)
            ->where('school_id', session('school_id'))
            ->orderBy('start_date')
            ->get(['id', 'name', 'description', 'start_date', 'end_date', 'grade_levels']);

        $gradeLevels = GradeLevel::where('school_id', session('school_id'))
            ->orderBy('level')
            ->get(['id', 'name', 'level']);

        return Inertia::render('public/admissions', [
            'periods' => $periods,
            'gradeLevels' => $gradeLevels,
        ]);
    }
}