<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use Inertia\Response;

class AboutController
{
    public function index(): Response
    {
        return Inertia::render('public/about', [
            'stats' => [
                ['number' => '500+', 'label' => 'public.studentsEnrolled'],
                ['number' => '50+', 'label' => 'public.qualifiedTeachers'],
                ['number' => '25+', 'label' => 'public.yearsOfExcellence'],
                ['number' => '15+', 'label' => 'public.academicPrograms'],
            ],
        ]);
    }
}
